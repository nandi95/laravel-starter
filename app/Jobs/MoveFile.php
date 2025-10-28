<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Move the file in storage.
 */
class MoveFile implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $fromLocation,
        public string $toLocation,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Storage::move($this->fromLocation, $this->toLocation);

        Log::info('Moved file in storage', ['from' => $this->fromLocation, 'to' => $this->toLocation]);
    }
}
