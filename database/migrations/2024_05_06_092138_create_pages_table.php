<?php

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Entities\Page;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('pages')) {
            return;
        }

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title')->unique();
            $table->text('content');
        });

        $this->createExistingPages();
    }

    private function createExistingPages(): void
    {
        $files = [
            'privacy_policy',
            'terms_conditions',
            'refund',
            'delete_my_account',
        ];

        foreach ($files as $file) {
            $path = storage_path($file . '.txt');

            try {
                $content = File::get($path);
            } catch (FileNotFoundException $e) {
                continue;
            }

            $page = new Page();
            $page->slug = $file;
            $page->title = trans("admin.$file");
            $page->content = $content;
            $page->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pages');
    }
}
