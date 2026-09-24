<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Wajibkan tanda tangan penerima" diganti "Wajibkan konfirmasi penerima" (PRD 8.22).
 *
 * Nilai yang sudah disimpan organisasi dipindahkan ke kunci baru, supaya organisasi
 * yang dulu mewajibkan tanda tangan tetap mewajibkan konfirmasi. Bila kunci baru
 * sudah ada (migrasi diulang), nilai lama dibuang tanpa menimpanya.
 */
return new class extends Migration
{
    private const KUNCI_LAMA = 'Pemeliharaan.WajibTandaTanganPenerima';

    private const KUNCI_BARU = 'Pemeliharaan.WajibKonfirmasiPenerima';

    public function up(): void
    {
        $this->pindahkan(self::KUNCI_LAMA, self::KUNCI_BARU);
    }

    public function down(): void
    {
        $this->pindahkan(self::KUNCI_BARU, self::KUNCI_LAMA);
    }

    private function pindahkan(string $dari, string $ke): void
    {
        $sudahAda = DB::table('KonfigurasiOrganisasi')->where('Kunci', $ke)->pluck('OrganisasiId');

        DB::table('KonfigurasiOrganisasi')
            ->where('Kunci', $dari)
            ->whereIn('OrganisasiId', $sudahAda)
            ->delete();

        DB::table('KonfigurasiOrganisasi')
            ->where('Kunci', $dari)
            ->update(['Kunci' => $ke]);
    }
};
