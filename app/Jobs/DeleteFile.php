<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Delete a file from the storage.
 */
class DeleteFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $path, public ?string $disk = null) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Storage::disk($this->disk ?? config('filesystem.default'))->delete($this->path);

        Log::info('Deleted file from storage', ['path' => $this->path, 'disk' => $this->disk]);
    }
}
