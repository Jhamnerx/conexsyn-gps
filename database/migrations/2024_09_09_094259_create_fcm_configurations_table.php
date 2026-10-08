<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Entities\FcmConfiguration;

class CreateFcmConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('fcm_configurations')) {
            Schema::create('fcm_configurations', function (Blueprint $table) {
                $table->id();
                $table->string('title')->unique();
                $table->boolean('is_default')->default(false);
                $table->string('project_id')->unique();
                $table->text('config');
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('fcm_tokens', 'project_id')) {
            Schema::table('fcm_tokens', function (Blueprint $table) {
                $table->string('project_id')->index()->nullable()->after('token');
            });
        }

        $this->insertCustomConfig();
    }

    private function insertCustomConfig(): void
    {
        $configPath = storage_path('app/firebase-config.json');

        if (!File::exists($configPath)) {
            return;
        }

        $configJson = File::get($configPath);
        $config = json_decode($configJson, true);

        File::delete($configPath);

        if (empty($config['project_id'])) {
            return;
        }

        $fcmConfig = new FcmConfiguration([
            'title' => $config['project_id'],
            'is_default' => true,
            'config' => $configJson,
        ]);
        $fcmConfig->project_id = $config['project_id'];
        $fcmConfig->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('fcm_configurations')) {
            return;
        }

        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->dropColumn('project_id');
        });

        $this->storeCustomConfigFile();

        Schema::dropIfExists('fcm_configurations');
    }

    private function storeCustomConfigFile(): void
    {
        $config = FcmConfiguration::firstWhere('is_default', 1);;

        if (!$config) {
            return;
        }

        $configPath = storage_path('app/firebase-config.json');

        if (File::exists($configPath)) {
            File::delete($configPath);
        }

        File::put($configPath, $config->config);
    }
}
