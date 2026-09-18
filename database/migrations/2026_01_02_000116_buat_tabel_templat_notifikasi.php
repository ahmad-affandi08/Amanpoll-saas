<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TemplatNotifikasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26)->nullable();
            $table->string('Kode', 100);
            $table->string('Kanal', 40);
            $table->text('JudulTemplat')->nullable();
            $table->longText('IsiTemplat');
            $table->json('Variabel')->nullable();
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['OrganisasiId', 'Kode', 'Kanal'], 'IdxTemplatNotifikasi');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TemplatNotifikasi');
    }
};
