<?php

declare(strict_types=1);

namespace App\Listeners\HealthChecks;

use App\Exceptions\HealthCheckException;
use Illuminate\Foundation\Events\DiagnosingHealth;

class HasSufficientDiskSpace
{
    private const int MIN_FREE_SPACE_MB = 1024; // 1GB minimum

    /**
     * Handle the event.
     *
     * @throws \Throwable
     */
    public function handle(DiagnosingHealth $event): void
    {
        $path = storage_path();
        $freeBytes = disk_free_space($path);

        throw_if($freeBytes === false, new HealthCheckException('Could not determine disk space for: ' . $path));

        $freeMB = $freeBytes / 1024 / 1024;

        throw_if($freeMB < self::MIN_FREE_SPACE_MB, new HealthCheckException(
            sprintf('Insufficient disk space: %.2f MB free (minimum: %d MB)', $freeMB, self::MIN_FREE_SPACE_MB)
        ));
    }
}
