<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SukuCadang', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('KategoriSukuCadangId', 26)->nullable();
            $table->string('Kode', 80);
            $table->string('Nama', 200);
            $table->string('NomorBagian', 160)->nullable();
            $table->string('KodeBatang', 255)->nullable();
            $table->string('SatuanDasar', 50);
            $table->decimal('StokMinimum', 20, 4)->default(0);
            $table->decimal('StokMaksimum', 20, 4)->nullable();
            $table->decimal('TitikPesanUlang', 20, 4)->nullable();
            $table->decimal('HargaRataRata', 20, 2)->default(0);
            $table->boolean('MemakaiBatch')->default(0);
            $table->boolean('MemakaiKadaluarsa')->default(0);
            $table->string('Status', 30)->default('Aktif');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'Kode'], 'UqSukuCadangKode');
            $table->index(['OrganisasiId', 'NomorBagian'], 'IdxSukuCadangNomorBagian');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('KategoriSukuCadangId')->references('Id')->on('KategoriSukuCadang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SukuCadang');
    }
};
