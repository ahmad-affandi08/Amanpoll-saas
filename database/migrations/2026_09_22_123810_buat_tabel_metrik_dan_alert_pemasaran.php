<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Metrik kampanye harian yang dihitung di muka, dan alert growth platform (MARKETING.md 5). */
return new class extends Migration
{
    public function up(): void
    {
        // Dihitung pekerjaan terjadwal; menghitungnya saat halaman dibuka berarti satu kueri per kampanye.
        Schema::create('MetrikKampanye', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('KampanyeId', 26)->nullable();
            $table->string('Channel', 80)->nullable();
            $table->date('Tanggal');
            $table->unsignedInteger('Visitor')->default(0);
            $table->unsignedInteger('Lead')->default(0);
            $table->unsignedInteger('Trial')->default(0);
            $table->unsignedInteger('Teraktivasi')->default(0);
            $table->unsignedInteger('Bayar')->default(0);
            $table->decimal('Revenue', 14, 2)->default(0);
            $table->dateTime('DihitungPada', 6);

            $table->unique(['Tanggal', 'KampanyeId', 'Channel'], 'UnqMetrikKampanyeHarian');
            $table->index(['Tanggal'], 'IdxMetrikKampanyeTanggal');
            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->nullOnDelete();
        });

        // Alert growth tidak punya organisasi maupun Pengguna tujuan, sedangkan mesin Notifikasi terikat pada keduanya.
        Schema::create('AlertPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80);
            $table->string('Tingkat', 20);
            $table->string('Judul', 190);
            $table->string('Isi', 500);
            $table->json('Rincian')->nullable();
            // Satu alert per kode per hari; pemeriksa yang berjalan tiap jam tidak menumpuk baris yang sama.
            $table->date('Tanggal');
            $table->dateTime('DiselesaikanPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6);

            $table->unique(['Kode', 'Tanggal'], 'UnqAlertPemasaranHarian');
            $table->index(['DiselesaikanPada', 'DibuatPada'], 'IdxAlertPemasaranTerbuka');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AlertPemasaran');
        Schema::dropIfExists('MetrikKampanye');
    }
};
