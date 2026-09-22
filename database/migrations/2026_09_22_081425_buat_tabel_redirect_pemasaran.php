<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redirect situs publik (MARKETING.md 9).
 *
 * Slug halaman yang berubah tanpa redirect kehilangan seluruh peringkat yang
 * sudah dibangun untuknya, dan menambalnya lewat deploy berarti setiap
 * perubahan slug menunggu rilis. Karena itu petanya disimpan sebagai data.
 *
 * `Dari` dinormalkan menjadi jalur berawalan `/` tanpa host: aturannya milik
 * situs publik, dan menyimpan host di dalamnya membuat aturan yang sama gagal
 * di lingkungan pengembangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RedirectPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Dari', 500)->unique('UnqRedirectPemasaranDari');

            // Kosong untuk 410: sumber daya dinyatakan hilang permanen dan
            // memang tidak punya tujuan.
            $table->string('Ke', 500)->nullable();
            $table->string('Kode', 3);
            $table->boolean('Aktif')->default(true);
            $table->string('Catatan', 500)->nullable();

            $table->unsignedBigInteger('JumlahDipakai')->default(0);
            $table->dateTime('TerakhirDipakaiPada', 6)->nullable();

            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->index(['Aktif'], 'IdxRedirectPemasaranAktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RedirectPemasaran');
    }
};
