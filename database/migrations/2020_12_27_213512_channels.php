<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Channels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!(Schema::hasTable("channels"))) {
            Schema::create('channels', function (Blueprint $table) {
                $table->boolean("configured")->nullable();
                $table->string("channel_id")->nullable()->change();
                $table->string("team_id")->nullable()->change();
                $table->string("slack_email_address")->nullable()->change();
            });
        }
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
