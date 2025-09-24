<?php

declare(strict_types=1);

namespace App\Traits;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use RuntimeException;

trait InteractsWithS3
{
    private ?S3Client $client = null;

    /**
     * @var array{
     *     region: string,
     *     key: string,
     *     secret: string,
     *     bucket: string,
     *     use_path_style_endpoint: bool,
     *     token: string,
     *     endpoint?: string,
     *     url?: string
     * }|array
     */
    private array $s3Config = [];

    /**
     * Ensure the required environment variables are available.
     *
     * @SuppressWarnings(PHPMD)
     */
    protected function ensureS3ConfigValuesSet(): void
    {
        /** @var array<string, string> $config */
        $config = config('filesystems.disks.s3');
        $s3Config = collect($config);

        $region = $s3Config->get('region');
        $pathStyleEndpoint = (bool) $s3Config->get('use_path_style_endpoint', false);
        $key = $s3Config->get('key');
        $secret = $s3Config->get('secret');
        $bucket = $s3Config->get('bucket');
        $endpoint = $s3Config->get('endpoint');
        $url = $s3Config->get('url');

        $this->s3Config = [
            'region' => $region,
            'secret' => $secret,
            'key' => $key,
            'use_path_style_endpoint' => $pathStyleEndpoint,
            'bucket' => $bucket
        ];

        if ($endpoint) {
            $this->s3Config['endpoint'] = $endpoint;
        }

        if ($url) {
            $this->s3Config['url'] = $url;
        }

        $missing = collect($this->s3Config)->whereNull();

        if ($missing->isNotEmpty()) {
            throw new RuntimeException(
                'Unable to issue signed URL. Missing configuration values for s3: ' . $missing->keys()->implode(', ')
            );
        }
    }

    /**
     * Get the configuration value(s).
     */
    public function getConfig(?string $key, mixed $default = null): array|string|null|bool
    {
        if (count($this->s3Config) === 0) {
            $this->ensureS3ConfigValuesSet();
        }

        if ($key) {
            return data_get($this->s3Config, $key, $default);
        }

        if (count($this->s3Config) === 0) {
            return value($default);
        }

        return $this->s3Config;
    }

    /**
     * Get the S3 storage client instance.
     *
     * @SuppressWarnings(PHPMD)
     */
    protected function storageClient(): S3Client
    {
        if (!is_null($this->client)) {
            return $this->client;
        }

        /** @var string $key */
        $key = $this->getConfig('key');
        /** @var string $secret */
        $secret = $this->getConfig('secret');
        /** @var string|null $token */
        $token = $this->getConfig('token');

        $config = [
            'region' => $this->getConfig('region'),
            'version' => 'latest',
            'signature_version' => 'v4',
            'use_path_style_endpoint' => $this->getConfig('use_path_style_endpoint', false),
            'credentials' => new Credentials($key, $secret, $token),
        ];

        if ($this->getConfig('endpoint')) {
            $config['endpoint'] = $this->getConfig('endpoint');
        }

        if ($this->getConfig('url')) {
            $config['url'] = $this->getConfig('url');
        }

        return $this->client = new S3Client($config);
    }
}
