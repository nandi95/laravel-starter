#!/bin/sh
# stop running as soon as any command this executes fails
set -e

# This script is currently designed to be run on the local machine
#  - not checking if openssh/curl is installed
#  - getting the ssh key from path
#  - assuming .ssh folder exists and owned

# List of required environment variables
REQUIRED_VARS="SSH_KEY_PATH SSH_USER SERVER"

# Check if each required variable is set
for VAR in $REQUIRED_VARS; do
  eval "VALUE=\$$VAR"
  if [ -z "$VALUE" ]; then
    echo "Error: $VAR is not set."
    exit 1
  fi
done

# Expand SSH_KEY_PATH if it starts with ~
if [ "${SSH_KEY_PATH#\~}" != "$SSH_KEY_PATH" ]; then
  SSH_KEY_PATH="$HOME${SSH_KEY_PATH#\~}"
fi

# Check if SSH key file exists
if [ ! -f "$SSH_KEY_PATH" ]; then
  echo "Error: SSH key file not found at $SSH_KEY_PATH."
  exit 1
fi

# --- Build image locally and push to registry ---
STACK_NAME=${1:-"test"}
REGISTRY=todo/change-me
# STAGE is either production or yeah... that's it. (docker-compose.${STAGE}.yml and .env.${STAGE} must be defined)
STAGE=${2:-"production"}

# if .env.${STAGE} or docker-compose.${STAGE} file doesn't exist, exit
if [ ! -f ".env.${STAGE}" ] || [ ! -f "docker-compose.${STAGE}.yml" ]; then
  echo "Error: .env.${STAGE} or docker-compose.${STAGE}.yml file doesn't exist."
  exit 1
fi

export LARAVEL_IMAGE=${REGISTRY}/lashbrill-laravel-api
export NUXT_IMAGE=${REGISTRY}/lashbrill-nuxt-web
NOW=$(date +%Y-%m-%d-%H-%M-%S)
GIT_SHA=$(git rev-parse --short HEAD)

echo "Building images..."
docker buildx build --platform linux/amd64 \
    --target="${STAGE}" \
    --build-arg NOW="${NOW}" \
    --build-arg GIT_SHA="${GIT_SHA}" \
    --tag ${NUXT_IMAGE}:latest \
    --file nuxt.Dockerfile .
docker buildx build --platform linux/amd64 \
    --target="${STAGE}" \
    --build-arg NOW="${NOW}" \
    --build-arg GIT_SHA="${GIT_SHA}" \
    --tag ${LARAVEL_IMAGE}:latest \
    --file laravel.Dockerfile .

echo "Pushing images..."
docker push ${LARAVEL_IMAGE}:latest
docker push ${NUXT_IMAGE}:latest

export DOTENV_NAME="${STACK_NAME}-dotenv-${NOW}"

# --- Deploy to swarm on controlling node ---

# add ssh key to ssh-agent from the private key path while removing new lines
tr -d '\r' < "$SSH_KEY_PATH" | ssh-add - > /dev/null

# add server to known hosts if doesn't already exist (has the side effect of sorting the file)
ssh-keyscan "${SERVER}" >> ~/.ssh/known_hosts && sort -u ~/.ssh/known_hosts -o ~/.ssh/known_hosts

# I command all docker commands shall run on the server henceforth https://docs.docker.com/reference/cli/docker/#environment-variables
export DOCKER_HOST=ssh://"${SSH_USER}"@"${SERVER}"

# if not a docker swarm manager, make it so
if [ "$(docker info --format '{{.Swarm.LocalNodeState}}')" != "active" ]; then
  echo "Node is not part of a Swarm. Initializing Swarm..."
  docker swarm init
fi

echo "Creating secret ${DOTENV_NAME} from .env.${STAGE} file..."
# Read the content of the .env.${STAGE} file and create a secret from it
# (the use of this is that the .env file not transferred in clear text to the nodes)
docker secret create "${DOTENV_NAME}" - < ".env.${STAGE}"

# used by the prod caddy file
CLOUDFLARE_API_TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' ".env.${STAGE}" | cut -d '=' -f2)

export CLOUDFLARE_API_TOKEN

# Check if the current node is part of a Swarm
if [ "$(docker info --format '{{.Swarm.LocalNodeState}}')" != "active" ]; then
  echo "Node is not part of a Swarm. Initializing Swarm..."
  docker swarm init
fi

# create a network for the proxy if doesn't exist
if [ -z "$(docker network ls --filter scope=swarm --filter name=proxy --quiet)" ]; then
    echo "Creating proxy network..."
    docker network create --scope swarm --driver=overlay proxy
fi

# create external volume caddy_data if doesn't exist
if [ -z "$(docker volume ls --filter name=caddy_data --quiet)" ]; then
    echo "Creating caddy_data volume..."
    docker volume create --name=caddy_data
fi

echo "Downloading docker-stack-wait script..."
# download docker-stack-wait script
curl -sSL -o /usr/local/bin/docker-stack-wait https://raw.githubusercontent.com/sudo-bmitch/docker-stack-wait/main/docker-stack-wait.sh
chmod +x /usr/local/bin/docker-stack-wait

echo "Deploying stack ${STACK_NAME}..."
# deploy the stack as detached
# using these compose files,
# prune any unused services
# and forward the registry login details to the nodes
# (the weird order of compose files is because of https://github.com/docker/cli/issues/2407)
docker stack deploy \
    --detach \
    --compose-file docker-compose."${STAGE}".yml --compose-file docker-compose.yml \
    --prune \
    --with-registry-auth \
    "${STACK_NAME}"

echo "Waiting for the stack to be deployed..."
docker-stack-wait "${STACK_NAME}"

echo "Removing old secrets..."
# remove all docker secrets that isn't DOTENV_NAME
docker secret ls --format "{{.ID}} {{.Name}}" | grep -v "${DOTENV_NAME}" | cut -d " " -f 1  | xargs docker secret rm
