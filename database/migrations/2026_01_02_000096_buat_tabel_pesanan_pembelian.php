<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PesananPembelian', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->char('PenyediaId', 26);
            $table->char('PermintaanPembelianId', 26)->nullable();
            $table->char('PenawaranPenyediaId', 26)->nullable();
            $table->char('PosAnggaranId', 26)->nullable();
            $table->date('TanggalPesanan');
            $table->date('TanggalKirimRencana')->nullable();
            $table->char('MataUang', 3)->default('IDR');
            $table->decimal('Subtotal', 20, 2)->default(0);
            $table->decimal('Pajak', 20, 2)->default(0);
            $table->decimal('Diskon', 20, 2)->default(0);
            $table->decimal('Total', 20, 2)->default(0);
            $table->string('Status', 40)->default('Draft');
            $table->text('Catatan')->nullable();
            $table->char('DibuatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqPesananPembelianNomor');
            $table->index(['OrganisasiId', 'Status', 'TanggalPesanan'], 'IdxPesananPembelianStatus');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
            $table->foreign('PermintaanPembelianId')->references('Id')->on('PermintaanPembelian');
            $table->foreign('PenawaranPenyediaId')->references('Id')->on('PenawaranPenyedia');
            $table->foreign('PosAnggaranId')->references('Id')->on('PosAnggaran');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PesananPembelian');
    }
};
