<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TingkatLayanan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Kode', 60);
            $table->string('Nama', 160);
            $table->text('Deskripsi')->nullable();
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Kode'], 'UqTingkatLayananKode');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TingkatLayanan');
    }
};
