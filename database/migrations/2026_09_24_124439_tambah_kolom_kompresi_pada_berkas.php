<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mesin kompresi berkas (PRD 11.1).
 *
 * - `MetodeKompresi`: `Tidak`, `Gzip`, atau `GambarUlang` (enum MetodeKompresi).
 * - `UkuranAsliByte`: ukuran berkas sebelum dipadatkan (yang dikirim pengguna).
 * - `UkuranTersimpanByte`: ukuran di disk; salinan fisik bersama dihitung per baris.
 * - `LokasiThumbnail`: thumbnail WebP di disk `MediaPenyimpanan` yang sama.
 *
 * `UkuranByte` tetap berarti ukuran yang diterima pengguna saat mengunduh. Baris
 * lama disimpan apa adanya, jadi ketiga ukurannya sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Berkas', function (Blueprint $table): void {
            $table->string('MetodeKompresi', 20)->default('Tidak')->after('HashSha256');
            $table->unsignedBigInteger('UkuranAsliByte')->nullable()->after('MetodeKompresi');
            $table->unsignedBigInteger('UkuranTersimpanByte')->nullable()->after('UkuranAsliByte');
            $table->string('LokasiThumbnail', 500)->nullable()->after('UkuranTersimpanByte');
        });

        DB::table('Berkas')
            ->whereNull('UkuranAsliByte')
            ->update([
                'UkuranAsliByte' => DB::raw('UkuranByte'),
                'UkuranTersimpanByte' => DB::raw('UkuranByte'),
            ]);
    }

    public function down(): void
    {
        Schema::table('Berkas', function (Blueprint $table): void {
            $table->dropColumn(['MetodeKompresi', 'UkuranAsliByte', 'UkuranTersimpanByte', 'LokasiThumbnail']);
        });
    }
};
