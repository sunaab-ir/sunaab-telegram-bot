<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelUserProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tel_user_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->string('full_name')->nullable();
            $table->enum('sex', ['man', 'woman'])->nullable();
            $table->string('mobile_number')->nullable();
            $table->unsignedInteger('county_id')->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->unsignedInteger('village_id')->nullable();
            $table->boolean('is_manual_worker')->nullable();
            $table->unsignedInteger('work_category')->nullable();
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
        Schema::dropIfExists('tel_user_profiles');
    }
}
