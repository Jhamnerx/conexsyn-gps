<?php namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\InputOption;

class GeoCacheSetupCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'server:geocache';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup database for geo cache';
    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $force = $this->option('force');

        $database = config('database.connections.sqlite.database');

        if (File::exists($database)) {
            if ( ! $force)
                return;
            
            $files = array_map(fn ($sub) => $database . $sub, ["", ".wal", ".shm", "-wal", "-shm"]);

            foreach ($files as $file) {
                if (File::exists($file))
                    File::delete($file);
            }
        }

        File::put($database, '');
        File::chmod($database, 0777);

        if (!Schema::connection('sqlite')->hasTable('cache')) {
            Schema::connection('sqlite')->create('cache', function (Blueprint $table) {
                $table->string('key', 255)->primary();
                $table->text('value')->nullable(false);
                $table->integer('expiration')->default(0);
            });
        }

        DB::connection('sqlite')->unprepared(<<<SQL
                PRAGMA journal_mode = WAL;
                SQL);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array();
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('force', null, InputOption::VALUE_OPTIONAL, 'Force option.', null),
        );
    }
}