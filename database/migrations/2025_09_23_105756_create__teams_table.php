<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->nullable()->unique()->comment('ID na API');
            $table->string('name')->nullable();
            $table->string('country')->nullable();
            $table->string('code')->nullable();
            $table->string('founded')->nullable();
            $table->string('venue')->nullable(); // nome do estadio
            $table->string('logo')->nullable(); // url logo (ou guarda só external_id e constrói a url)
            $table->longText('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('teams');
    }
};
