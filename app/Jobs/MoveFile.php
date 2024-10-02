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
        public string $from,
        public string $to,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Storage::move($this->from, $this->to);

        Log::info('Moved file in storage', ['from' => $this->from, 'to' => $this->to]);
    }
}
