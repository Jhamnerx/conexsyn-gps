<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Tobuli\Entities\User;
use Tobuli\Helpers\Tracker;

class TrackerConfigWithRestart extends Job implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, SerializesModels;

    private ?User $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user = null)
    {
        $this->queue = 'tracker';
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $tracker = new Tracker();
        $tracker->config()->generate();
        $tracker->restart();
    }
}
