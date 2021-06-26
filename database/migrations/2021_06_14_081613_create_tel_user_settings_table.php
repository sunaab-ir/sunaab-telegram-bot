<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tel_user_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('tel_user_id');
            $table->boolean('receive_ad')->default(true);
            $table->boolean('receive_village')->default(true);
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
        Schema::dropIfExists('tel_user_settings');
    }
}
