# Path to Oh My Zsh installation
export ZSH="$HOME/.oh-my-zsh"

# Theme (you can change this to: robbyrussell, agnoster, powerlevel10k, etc.)
ZSH_THEME="jonathan"

source $ZSH/oh-my-zsh.sh

# Custom environment variables for Laravel development
export PATH="$HOME/.composer/vendor/bin:$PATH"
export PATH="./vendor/bin:$PATH"

alias art="php artisan"
alias cls="clear"
alias routes="php artisan route:list"
alias composer="php -d memory_limit=-1 $(which composer)"
