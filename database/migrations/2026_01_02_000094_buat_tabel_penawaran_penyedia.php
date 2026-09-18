<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenawaranPenyedia', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PermintaanPenawaranId', 26);
            $table->char('PenyediaId', 26);
            $table->string('NomorPenawaran', 120)->nullable();
            $table->date('TanggalPenawaran');
            $table->date('BerlakuSampai')->nullable();
            $table->char('MataUang', 3)->default('IDR');
            $table->decimal('Subtotal', 20, 2)->default(0);
            $table->decimal('Pajak', 20, 2)->default(0);
            $table->decimal('Diskon', 20, 2)->default(0);
            $table->decimal('Total', 20, 2)->default(0);
            $table->string('Status', 40)->default('Diajukan');
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PermintaanPenawaranId', 'PenyediaId', 'Status'], 'IdxPenawaranPenyedia');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PermintaanPenawaranId')->references('Id')->on('PermintaanPenawaran');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenawaranPenyedia');
    }
};
