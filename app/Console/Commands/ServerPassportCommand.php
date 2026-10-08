<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\Client;

class ServerPassportCommand extends Command {

    const CLIENT_NAME_CLIENT_LITE = 'ClientLite Password Grant Client';
    const CLIENT_NAME_TRACKER_LITE = 'TrackerLite Password Grant Client';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server:passport 
                                        {--force : Overwrite keys they already exist} 
                                        {--length=4096 : The length of the private key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare Passport for use.';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('passport:keys', ['--force' => $this->option('force'), '--length' => $this->option('length')]);

        if (!self::getClientAppClientLite())
            $this->call('passport:client', [
                '--password' => true,
                '--name' => self::CLIENT_NAME_CLIENT_LITE,
                '--provider' => 'users'
            ]);

        if (!self::getClientAppTrackerLite())
            $this->call('passport:client', [
                '--password' => true,
                '--name' => self::CLIENT_NAME_TRACKER_LITE,
                '--provider' => 'devices'
            ]);

        return self::SUCCESS;
    }

    /**
     * @return Client|null
     */
    public static function getClientAppClientLite()
    {
        return Client::where('name', self::CLIENT_NAME_CLIENT_LITE)->first();
    }

    /**
     * @return Client|null
     */
    public static function getClientAppTrackerLite()
    {
        return Client::where('name', self::CLIENT_NAME_TRACKER_LITE)->first();
    }
}
