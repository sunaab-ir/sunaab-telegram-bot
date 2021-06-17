<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelUserTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tel_user_tracks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('tel_user_id');
            $table->unsignedInteger('tel_process_id');
            $table->string('process_state');
            $table->string('sub_process')->nullable();
            $table->enum('type', ['in', ['out']])->default('in');
            $table->string('entry_type');
            $table->longText('user_input')->nullable();
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
        Schema::dropIfExists('tel_user_tracks');
    }
}
