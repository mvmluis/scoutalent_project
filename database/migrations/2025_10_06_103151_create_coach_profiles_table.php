<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_profiles', function (Blueprint $table) {
            $table->id();

            // Relação 1:1 com coachs
            $table->unsignedBigInteger('coach_id')->unique()->index();
            $table->foreign('coach_id')->references('id')->on('coachs')->onDelete('cascade');

            // Data fim de contrato
            $table->date('contract_end')->nullable()->comment('Data de fim do contrato');

            // Campos de estatísticas personalizáveis
            $table->string('stat1_label')->nullable();
            $table->string('stat1_value')->nullable();

            $table->string('stat2_label')->nullable();
            $table->string('stat2_value')->nullable();

            $table->string('stat3_label')->nullable();
            $table->string('stat3_value')->nullable();

            // Campo JSON extra (para expansão futura, se necessário)
            $table->json('meta')->nullable()->comment('Outros dados personalizados');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_profiles');
    }
};
