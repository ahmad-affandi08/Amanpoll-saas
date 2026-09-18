<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PembayaranPenyedia', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('TagihanPenyediaId', 26);
            $table->string('NomorPembayaran', 120);
            $table->date('TanggalBayar');
            $table->decimal('Jumlah', 20, 2);
            $table->string('Metode', 60)->nullable();
            $table->string('Referensi', 180)->nullable();
            $table->char('DibuatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'NomorPembayaran'], 'UqPembayaranPenyedia');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('TagihanPenyediaId')->references('Id')->on('TagihanPenyedia');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PembayaranPenyedia');
    }
};
