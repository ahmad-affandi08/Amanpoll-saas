<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AturanTingkatLayanan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('TingkatLayananId', 26);
            $table->string('Prioritas', 40);
            $table->unsignedInteger('MenitRespons')->nullable();
            $table->unsignedInteger('MenitMulaiPengerjaan')->nullable();
            $table->unsignedInteger('MenitPenyelesaian')->nullable();
            $table->boolean('MenghitungJamKerja')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['TingkatLayananId', 'Prioritas'], 'UqAturanTingkatLayananPrioritas');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('TingkatLayananId')->references('Id')->on('TingkatLayanan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AturanTingkatLayanan');
    }
};
