<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TransaksiAnggaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PosAnggaranId', 26);
            $table->string('Jenis', 50);
            $table->string('ReferensiJenis', 80)->nullable();
            $table->char('ReferensiId', 26)->nullable();
            $table->decimal('Jumlah', 20, 2);
            $table->date('Tanggal');
            $table->text('Keterangan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PosAnggaranId', 'Tanggal', 'Jenis'], 'IdxTransaksiAnggaran');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PosAnggaranId')->references('Id')->on('PosAnggaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TransaksiAnggaran');
    }
};
