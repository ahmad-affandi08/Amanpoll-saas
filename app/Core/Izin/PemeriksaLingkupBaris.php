<?php

declare(strict_types=1);

namespace App\Core\Izin;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Menjawab "apakah lingkup pengguna ini mencakup baris ini" untuk satu baris
 * yang sudah di tangan (PRD 8.21).
 *
 * ScopeLingkup menyaring kueri untuk pengguna yang sedang masuk; penugasan
 * teknisi dan penerima notifikasi butuh pertanyaan kebalikannya: dari sekian
 * pengguna, siapa yang dapat melihat tiket ini. Semantiknya sengaja sama
 * persis dengan ScopeLingkup -- sumbernya LingkupAkses yang sama, dan baris
 * cocok bila salah satu kolom `kolomLingkup()` masuk daftar yang diizinkan --
 * supaya server tidak pernah menugaskan tiket kepada orang yang lalu tidak
 * dapat membukanya.
 *
 * Tidak memeriksa izin. Pengguna tanpa peran dianggap tanpa batas persis
 * seperti di LingkupAkses, jadi pemanggil tetap wajib menyaring calon menurut
 * peran atau izinnya lebih dulu.
 *
 * Lingkup dihitung di organisasi pemilik baris, bukan konteks yang sedang
 * berlaku, karena eskalasi SLA berjalan dari cron tanpa konteks organisasi --
 * tanpa itu LingkupAkses menganggap semua orang tanpa batas.
 */
final class PemeriksaLingkupBaris
{
    public function __construct(
        private readonly LingkupAkses $lingkup,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    public function mencakup(string $penggunaId, Model&BerlingkupUnit $baris): bool
    {
        return $this->dalamOrganisasiBaris($baris, fn (): bool => $this->cocok($penggunaId, $baris));
    }

    /**
     * Menyaring daftar pengguna menjadi yang lingkupnya mencakup baris, urutan masukan dipertahankan.
     *
     * Satu kueri PenggunaPeran untuk seluruh calon lebih dulu memisahkan yang
     * pasti tanpa batas (tanpa peran berlaku, atau satu penugasan tanpa
     * cakupan) -- keadaan seluruh pengguna di organisasi yang tidak memakai
     * lingkup, sehingga di sana tidak ada kueri per pengguna sama sekali.
     * Hanya pengguna berlingkup yang dihitung lewat LingkupAkses, yang
     * menyimpan hasilnya di cache.
     *
     * @param  iterable<mixed, string>  $penggunaIds
     * @return list<string>
     */
    public function penggunaYangMencakup(Model&BerlingkupUnit $baris, iterable $penggunaIds): array
    {
        $calon = [];

        foreach ($penggunaIds as $penggunaId) {
            $calon[(string) $penggunaId] = true;
        }

        $calon = array_keys($calon);

        if ($calon === []) {
            return [];
        }

        return $this->dalamOrganisasiBaris($baris, function () use ($calon, $baris): array {
            $pastiTanpaBatas = $this->penggunaPastiTanpaBatas($calon);

            return array_values(array_filter(
                $calon,
                fn (string $penggunaId): bool => isset($pastiTanpaBatas[$penggunaId]) || $this->cocok($penggunaId, $baris),
            ));
        });
    }

    /** Meniru ScopeLingkup::apply() untuk satu baris. */
    private function cocok(string $penggunaId, Model&BerlingkupUnit $baris): bool
    {
        if ($this->lingkup->tanpaBatas($penggunaId)) {
            return true;
        }

        $diizinkan = [
            'unit' => $this->lingkup->unitDiizinkan($penggunaId),
            'lokasi' => $this->lingkup->lokasiDiizinkan($penggunaId),
        ];

        foreach ($baris->kolomLingkup() as $kolom => $jenis) {
            $nilai = $baris->getAttribute($kolom);

            if ($nilai !== null && in_array((string) $nilai, $diizinkan[$jenis], true)) {
                return true;
            }
        }

        // Fail-closed seperti ScopeLingkup: berlingkup tanpa padanan berarti tidak mencakup.
        return false;
    }

    /**
     * Pengguna yang pasti tanpa batas menurut aturan LingkupAkses::hitung().
     *
     * @param  list<string>  $penggunaIds
     * @return array<string, true>
     */
    private function penggunaPastiTanpaBatas(array $penggunaIds): array
    {
        $organisasiId = $this->konteks->id();

        if ($organisasiId === null) {
            return array_fill_keys($penggunaIds, true);
        }

        $penugasan = DB::table('PenggunaPeran')
            ->where('OrganisasiId', $organisasiId)
            ->whereIn('PenggunaId', $penggunaIds)
            ->where(function ($q): void {
                $q->whereNull('BerlakuMulai')->orWhere('BerlakuMulai', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('BerlakuSampai')->orWhere('BerlakuSampai', '>=', now());
            })
            ->get(['PenggunaId', 'UnitOrganisasiId', 'LokasiId']);

        $berperan = [];
        $tanpaCakupan = [];

        foreach ($penugasan as $satu) {
            $penggunaId = (string) $satu->PenggunaId;
            $berperan[$penggunaId] = true;

            if ($satu->UnitOrganisasiId === null && $satu->LokasiId === null) {
                $tanpaCakupan[$penggunaId] = true;
            }
        }

        $hasil = $tanpaCakupan;

        foreach ($penggunaIds as $penggunaId) {
            if (! isset($berperan[$penggunaId])) {
                $hasil[$penggunaId] = true;
            }
        }

        return $hasil;
    }

    /**
     * @template THasil
     *
     * @param  callable(): THasil  $aksi
     * @return THasil
     */
    private function dalamOrganisasiBaris(Model $baris, callable $aksi): mixed
    {
        $organisasiBaris = $baris->getAttribute('OrganisasiId');
        $asal = $this->konteks->id();

        if ($organisasiBaris === null || (string) $organisasiBaris === $asal) {
            return $aksi();
        }

        $this->konteks->tetapkan((string) $organisasiBaris);

        try {
            return $aksi();
        } finally {
            $this->konteks->tetapkan($asal);
        }
    }
}
