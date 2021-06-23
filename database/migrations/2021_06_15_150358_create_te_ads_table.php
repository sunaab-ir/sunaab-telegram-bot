<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('te_ads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('creator_user_id')->nullable();
            $table->string('title')->nullable();
            $table->longText('photo_file_id')->nullable();
            $table->mediumText('ad_text')->nullable();
            $table->unsignedInteger('county_id')->nullable();
            $table->unsignedInteger('village_id')->nullable();
            $table->enum('target_sex', ['all', 'man', 'woman'])->default('all');
            $table->integer('valid_time')->nullable();
            $table->enum('state', [1,2,3,4,5,6])->default(1);
            $table->integer('worker_count')->default(1);
            $table->unsignedInteger('work_category')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('te_ads');
    }
}
