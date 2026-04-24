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
        Schema::create('player_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id')->index();
            $table->date('report_date')->nullable();
            $table->string('author')->nullable(); // quem fez
            $table->text('observations')->nullable();
            $table->tinyInteger('scoutalent_rentabilidade')->nullable()->comment('Valores -5 a 5');
            $table->tinyInteger('scoutalent_potencial')->nullable()->comment('Valores -5 a 5');
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_reports');
    }
};
