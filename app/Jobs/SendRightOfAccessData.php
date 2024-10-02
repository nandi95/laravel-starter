<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\RightOfAccessData;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRightOfAccessData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = [
            'user' => $this->user->only([
                'name',
                'email',
                'avatar',
            ]),
            'oauth_providers' => $this->user->userProviders->pluck(['name']),
            // todo - add more data
        ];

        Mail::to($this->user->email)->send(new RightOfAccessData($data));

        Log::info('Sent right of access data to user', ['user_id' => $this->user->id]);
    }
}
