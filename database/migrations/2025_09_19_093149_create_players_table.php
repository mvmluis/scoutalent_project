<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayersTable extends Migration
{
    public function up()
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();

            // id externo da API (player.id) — único
            $table->unsignedBigInteger('external_id')->nullable()->unique()->comment('ID externo fornecido pela API');

            // identificação / biografia
            $table->string('name')->nullable()->comment('Nome do jogador');
            $table->unsignedSmallInteger('age')->nullable()->comment('Idade');
            $table->string('nationality')->nullable()->comment('Nacionalidade');

            // 🆕 Foto do jogador
            $table->string('photo')->nullable()->comment('URL da foto do jogador');

            // medidas (texto porque por vezes vem "179 cm" / "6\'1\"" etc)
            $table->string('height')->nullable()->comment('Altura (ex: 179 cm)');
            $table->string('weight')->nullable()->comment('Peso (ex: 75 kg)');

            // nascimento (separado para consulta/filtro)
            $table->date('birth_date')->nullable()->comment('Data de nascimento');

            // equipa (dados da estatística / season)
            $table->unsignedBigInteger('team_id')->nullable()->index()->comment('ID da equipa (se existir na BD local)');
            $table->string('team_name')->nullable()->comment('Nome da equipa na season');

            // 🆕 Novos campos de estatísticas adicionais
            $table->string('position')->nullable()->comment('Posição do jogador (ex: Midfielder, Defender, Forward)');
            $table->decimal('rating', 4, 2)->nullable()->comment('Classificação média do jogador (ex: 7.45)');

            // estatísticas principais
            $table->unsignedSmallInteger('appearances')->nullable()->comment('Aparições / jogos');
            $table->unsignedInteger('minutes')->nullable()->comment('Minutos jogados');
            $table->unsignedSmallInteger('goals')->nullable()->comment('Golos');
            $table->unsignedSmallInteger('yellow_cards')->nullable()->comment('Cartões amarelos');
            $table->unsignedSmallInteger('red_cards')->nullable()->comment('Cartões vermelhos');

            // JSON completo da API para referência
            $table->json('meta')->nullable()->comment('JSON completo retornado pela API');

            // timestamps Laravel
            $table->timestamps();

            // se quiseres soft deletes:
            // $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('players');
    }
}
