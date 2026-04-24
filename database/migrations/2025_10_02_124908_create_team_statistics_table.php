<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('league_id')->nullable()->index();
            $table->integer('season')->nullable()->index();
            $table->json('data')->nullable();
            $table->string('form')->nullable();
            $table->decimal('goals_for_avg', 6, 2)->nullable();
            $table->decimal('goals_against_avg', 6, 2)->nullable();
            $table->integer('fixtures_played')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['team_id','league_id','season'], 'team_stats_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_statistics');
    }
};
