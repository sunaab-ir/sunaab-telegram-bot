<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tel_users', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('chat_id');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->boolean('is_bot')->nullable();
            $table->boolean('is_admin')->default(0);
            $table->integer('last_bot_message_id');
            $table->integer('last_bot_message_date')->default(time());
            $table->integer('last_user_message_id');
            $table->integer('last_user_message_date')->default(time());
            $table->string('process_type')->default('normal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tel_users');
    }
}
