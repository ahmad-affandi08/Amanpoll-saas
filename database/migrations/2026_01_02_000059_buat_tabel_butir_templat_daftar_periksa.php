<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ButirTemplatDaftarPeriksa', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('TemplatDaftarPeriksaId', 26);
            $table->integer('Urutan')->default(0);
            $table->string('Kode', 80)->nullable();
            $table->text('Pertanyaan');
            $table->string('TipeJawaban', 50);
            $table->string('Satuan', 50)->nullable();
            $table->boolean('Wajib')->default(0);
            $table->decimal('NilaiMinimum', 20, 6)->nullable();
            $table->decimal('NilaiMaksimum', 20, 6)->nullable();
            $table->json('Pilihan')->nullable();
            $table->boolean('BuktiFotoWajib')->default(0);
            $table->json('MemicuTemuanJika')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['TemplatDaftarPeriksaId', 'Urutan'], 'IdxButirTemplatDaftarPeriksa');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('TemplatDaftarPeriksaId')->references('Id')->on('TemplatDaftarPeriksa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ButirTemplatDaftarPeriksa');
    }
};
