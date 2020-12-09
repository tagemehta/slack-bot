<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emails', function(Blueprint $table) {
            $table->id();
            $table->string('from_email_address');
            //Who sent the email
            $table->string('email_id');
            $table->string('thread_value');
            $table->string('reply_to_address');
            $table->string('email_subject');
            $table->longText('old_email_body');
            $table->string("old_email_date");
            $table->string("old_email_original_address");
        });
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
