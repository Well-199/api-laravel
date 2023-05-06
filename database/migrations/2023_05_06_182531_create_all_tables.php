<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('avatar')->default('default.png');
            $table->string('email')->unique();
            $table->string('password');
        });
        Schema::create('userfavorites', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('professional_id');
        });
        Schema::create('userappointments', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('professional_id');
            $table->datetime('ap_datetime');
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('avatar')->default('default.png');
            $table->float('stars')->default(0);
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });
        Schema::create('professionalphotos', function (Blueprint $table) {
            $table->id();
            $table->integer('professional_id');
            $table->string('url');
        });
        Schema::create('professionalreviews', function (Blueprint $table) {
            $table->id();
            $table->integer('professional_id');
            $table->float('rate');
        });
        Schema::create('professionalservices', function (Blueprint $table) {
            $table->id();
            $table->integer('professional_id');
            $table->string('name');
            $table->float('price');
        });
        Schema::create('professionaltestimonials', function (Blueprint $table) {
            $table->id();
            $table->integer('professional_id');
            $table->string('name');
            $table->float('rate');
            $table->string('body');
        });
        Schema::create('professionalavailability', function (Blueprint $table) {
            $table->id();
            $table->integer('professional_id');
            $table->integer('weekday');
            $table->text('hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('userfavorites');
        Schema::dropIfExists('userappointments');
        Schema::dropIfExists('professionals');
        Schema::dropIfExists('professionalphotos');
        Schema::dropIfExists('professionalreviews');
        Schema::dropIfExists('professionalservices');
        Schema::dropIfExists('professionaltestimonials');
        Schema::dropIfExists('professionalavailability');
    }
};
