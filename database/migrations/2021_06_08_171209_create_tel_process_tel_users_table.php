<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelProcessTelUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tel_process_tel_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tel_process_id');
            $table->unsignedInteger('tel_user_id');
            $table->string('sub_process')->nullable();
            $table->enum('process_state', ['normal', 'input', 'processing'])->default('normal');
            $table->json('tmp_data')->nullable();
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
        Schema::dropIfExists('tel_process_tel_users');
    }
}
