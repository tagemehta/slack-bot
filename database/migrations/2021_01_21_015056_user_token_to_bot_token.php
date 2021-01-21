<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserTokenToBotToken extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('slack_workspaces', function (Blueprint $table) {
            // $table->renameColumn('user_token', 'bot_token');
            $table->string("team_id")->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('slack_workspaces', function (Blueprint $table) {
            $table->renameColumn('bot_token', 'user_token');

        });
    }
}
