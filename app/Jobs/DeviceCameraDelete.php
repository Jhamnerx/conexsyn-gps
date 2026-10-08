<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tobuli\Services\FtpUserService;

class DeviceCameraDelete extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private string $ftpUsername;
    private FtpUserService $ftpUserService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $ftpUsername)
    {
        $this->queue = 'service';
        $this->ftpUsername = $ftpUsername;
        $this->ftpUserService = new FtpUserService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->ftpUserService->removeFtpUser($this->ftpUsername);
    }
}
