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
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id')->unique()->index(); // 1-1 com players

            $table->decimal('market_value', 12, 2)->nullable()->comment('Valor de mercado €');
            $table->date('contract_end')->nullable();

            $table->tinyInteger('scoutalent_rentabilidade')->nullable()->comment('Valores -5 a 5');
            $table->tinyInteger('scoutalent_potencial')->nullable()->comment('Valores -5 a 5');

            // 6 campos de estatísticas (label + value)
            for ($i = 1; $i <= 6; $i++) {
                $table->string("stat{$i}_label")->nullable()->comment("Título da estatística {$i}");
                $table->string("stat{$i}_value")->nullable()->comment("Valor da estatística {$i}");
            }

            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
    }
};
