<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature flag tingkat platform (MARKETING.md 31).
 *
 * Berbeda dari FiturPaket yang menentukan apa yang dibeli satu tenant, flag ini
 * menentukan apa yang sudah hidup di produk sama sekali — dipakai untuk
 * menyalakan modul pemasaran secara bertahap tanpa menempel ke paket mana pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('FiturPlatform', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 100);
            $table->string('Nama', 180);
            $table->text('Keterangan')->nullable();
            $table->boolean('Aktif')->default(false);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['Kode'], 'UqFiturPlatformKode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('FiturPlatform');
    }
};
