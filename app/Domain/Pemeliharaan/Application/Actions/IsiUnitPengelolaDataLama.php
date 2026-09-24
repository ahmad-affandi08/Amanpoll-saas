<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Application\Services\PenentuUnitPengelola;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi unit pengelola yang masih kosong pada data yang tercatat sebelum FASE 40 (PRD 8.21).
 *
 * Tidak pernah berjalan diam-diam: hanya lewat perintah artisan
 * `pemeliharaan:isi-unit-pengelola`, yang selalu menampilkan rencananya lebih
 * dulu. Aturan turunannya sama dengan tiket baru karena dibaca dari
 * PenentuUnitPengelola yang sama:
 * - rencana kalibrasi: unit pengelola asetnya;
 * - rencana pemeliharaan: unit pengelola asetnya, hanya bila seluruh asetnya
 *   dikelola satu unit yang sama (rencana campuran dibiarkan kosong);
 * - keluhan: kategori (naik ke induk) → aset;
 * - perintah kerja: keluhan asal → aset (utama) → rencana preventif/kalibrasi asal.
 *
 * Nilai yang sudah terisi tidak pernah ditimpa, jadi menjalankannya berulang
 * tidak mengubah apa pun dan tidak menulis audit baru. Setiap kelompok baris
 * yang diisi tercatat di audit beserta Id-nya, supaya bisa ditelusuri dan
 * dikembalikan.
 *
 * Baris dibaca per potongan supaya organisasi dengan ratusan ribu tiket tidak
 * dimuat sekaligus. Berjalan tanpa pengguna masuk, jadi ScopeLingkup tidak
 * berlaku; tenancy tetap, lewat konteks organisasi yang ditetapkan di sini.
 */
final class IsiUnitPengelolaDataLama
{
    public const AKSI_AUDIT = 'UnitPengelola.DataLamaDiisi';

    /** Urutan pengisian: rencana dan keluhan lebih dulu karena perintah kerja mewarisi dari keduanya. */
    public const TABEL = ['RencanaKalibrasi', 'RencanaPemeliharaan', 'Keluhan', 'PerintahKerja'];

    private const UKURAN_POTONGAN = 500;

    /** Batas memo turunan aset per jalan; dikosongkan saat penuh supaya memori tetap datar. */
    private const BATAS_MEMO_ASET = 5000;

    /** @var array<string, ?string> */
    private array $memoKategori = [];

    /** @var array<string, ?string> */
    private array $memoAset = [];

    /** @var array<string, ?string> Unit efektif (tersimpan atau turunan) per RencanaPemeliharaan.Id. */
    private array $unitRencanaPemeliharaan = [];

    /** @var array<string, ?string> Unit efektif (tersimpan atau turunan) per RencanaKalibrasi.Id. */
    private array $unitRencanaKalibrasi = [];

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly KonteksOrganisasi $konteks,
        private readonly LayananAudit $audit,
        private readonly PenentuUnitPengelola $penentu,
    ) {}

    /**
     * Apa yang akan diisi pada satu organisasi, tanpa menulis apa pun.
     *
     * @return array<string, array{Kosong: int, Diisi: int, PerUnit: array<string, int>}> kunci: nama tabel
     */
    public function rencana(string $organisasiId): array
    {
        return $this->proses($organisasiId, tulis: false);
    }

    /**
     * Mengisi dan mencatat audit; hasilnya berbentuk sama dengan rencana().
     *
     * @return array<string, array{Kosong: int, Diisi: int, PerUnit: array<string, int>}>
     */
    public function jalankan(string $organisasiId): array
    {
        return $this->proses($organisasiId, tulis: true);
    }

    /**
     * @return array<string, array{Kosong: int, Diisi: int, PerUnit: array<string, int>}>
     */
    private function proses(string $organisasiId, bool $tulis): array
    {
        if ($this->konteks->ada() && $this->konteks->wajibId() !== $organisasiId) {
            throw new AturanBisnisDilanggar('Unit pengelola tidak boleh diisi untuk organisasi lain.');
        }

        $hasil = array_fill_keys(self::TABEL, ['Kosong' => 0, 'Diisi' => 0, 'PerUnit' => []]);

        // Tanpa satu pun unit bertanda Mengelola Aset, tidak ada aset atau kategori
        // yang bisa menunjuk unit pengelola; seluruh tabel tidak perlu dipindai.
        if (! DB::table('UnitOrganisasi')->where('OrganisasiId', $organisasiId)->where('MengelolaAset', true)->exists()) {
            return $hasil;
        }

        $konteksSebelumnya = $this->konteks->id();
        $this->konteks->tetapkan($organisasiId);
        $this->memoKategori = [];
        $this->memoAset = [];
        $this->unitRencanaPemeliharaan = [];
        $this->unitRencanaKalibrasi = [];

        try {
            $hasil['RencanaKalibrasi'] = $this->isiRencanaKalibrasi($organisasiId, $tulis);
            $hasil['RencanaPemeliharaan'] = $this->isiRencanaPemeliharaan($organisasiId, $tulis);
            $hasil['Keluhan'] = $this->isiKeluhan($organisasiId, $tulis);
            $hasil['PerintahKerja'] = $this->isiPerintahKerja($organisasiId, $tulis);

            return $hasil;
        } finally {
            $this->konteks->tetapkan($konteksSebelumnya);
        }
    }

    /**
     * @return array{Kosong: int, Diisi: int, PerUnit: array<string, int>}
     */
    private function isiRencanaKalibrasi(string $organisasiId, bool $tulis): array
    {
        $ringkasan = ['Kosong' => 0, 'Diisi' => 0, 'PerUnit' => []];

        DB::table('RencanaKalibrasi')
            ->where('OrganisasiId', $organisasiId)
            ->whereNull('UnitPengelolaId')
            ->select(['Id', 'AsetId'])
            ->chunkById(self::UKURAN_POTONGAN, function (Collection $potongan) use ($organisasiId, $tulis, &$ringkasan): void {
                $turunan = [];

                foreach ($potongan as $baris) {
                    $unit = $this->unitAset($this->teks($baris->AsetId));
                    $this->unitRencanaKalibrasi[$this->teks($baris->Id)] = $unit;
                    $turunan[$this->teks($baris->Id)] = $unit;
                }

                $this->terapkan('RencanaKalibrasi', $organisasiId, $turunan, $tulis, $ringkasan);
            }, 'Id');

        return $ringkasan;
    }

    /**
     * Rencana pemeliharaan mencakup banyak aset; unitnya hanya diturunkan bila
     * seluruh aset (yang belum dihapus) dikelola unit yang sama. Satu aset tanpa
     * unit pengelola sudah cukup membuatnya dibiarkan kosong.
     *
     * @return array{Kosong: int, Diisi: int, PerUnit: array<string, int>}
     */
    private function isiRencanaPemeliharaan(string $organisasiId, bool $tulis): array
    {
        $ringkasan = ['Kosong' => 0, 'Diisi' => 0, 'PerUnit' => []];

        DB::table('RencanaPemeliharaan')
            ->where('OrganisasiId', $organisasiId)
            ->whereNull('UnitPengelolaId')
            ->select(['Id'])
            ->chunkById(self::UKURAN_POTONGAN, function (Collection $potongan) use ($organisasiId, $tulis, &$ringkasan): void {
                $ids = $potongan->map(fn (object $baris): string => $this->teks($baris->Id))->all();

                $sebaran = DB::table('RencanaPemeliharaanAset as rpa')
                    ->join('Aset as a', 'a.Id', '=', 'rpa.AsetId')
                    ->where('rpa.OrganisasiId', $organisasiId)
                    ->whereIn('rpa.RencanaPemeliharaanId', $ids)
                    ->whereNull('a.DihapusPada')
                    ->groupBy('rpa.RencanaPemeliharaanId')
                    ->selectRaw('rpa.RencanaPemeliharaanId AS RencanaId, COUNT(*) AS Jumlah, COUNT(a.UnitPengelolaId) AS Berunit, COUNT(DISTINCT a.UnitPengelolaId) AS Ragam, MAX(a.UnitPengelolaId) AS Unit')
                    ->get()
                    ->keyBy('RencanaId');

                $turunan = [];

                foreach ($ids as $rencanaId) {
                    $baris = $sebaran->get($rencanaId);
                    $seragam = $baris !== null
                        && (int) $baris->Jumlah > 0
                        && (int) $baris->Berunit === (int) $baris->Jumlah
                        && (int) $baris->Ragam === 1;
                    $unit = $seragam ? $this->teksAtauNull($baris->Unit) : null;

                    $this->unitRencanaPemeliharaan[$rencanaId] = $unit;
                    $turunan[$rencanaId] = $unit;
                }

                $this->terapkan('RencanaPemeliharaan', $organisasiId, $turunan, $tulis, $ringkasan);
            }, 'Id');

        return $ringkasan;
    }

    /**
     * @return array{Kosong: int, Diisi: int, PerUnit: array<string, int>}
     */
    private function isiKeluhan(string $organisasiId, bool $tulis): array
    {
        $ringkasan = ['Kosong' => 0, 'Diisi' => 0, 'PerUnit' => []];

        DB::table('Keluhan')
            ->where('OrganisasiId', $organisasiId)
            ->whereNull('UnitPengelolaId')
            ->whereNull('DihapusPada')
            ->select(['Id', 'KategoriKeluhanId', 'AsetId'])
            ->chunkById(self::UKURAN_POTONGAN, function (Collection $potongan) use ($organisasiId, $tulis, &$ringkasan): void {
                $turunan = [];

                foreach ($potongan as $baris) {
                    $turunan[$this->teks($baris->Id)] = $this->unitKeluhan(
                        $this->teksAtauNull($baris->KategoriKeluhanId),
                        $this->teksAtauNull($baris->AsetId),
                    );
                }

                $this->terapkan('Keluhan', $organisasiId, $turunan, $tulis, $ringkasan);
            }, 'Id');

        return $ringkasan;
    }

    /**
     * @return array{Kosong: int, Diisi: int, PerUnit: array<string, int>}
     */
    private function isiPerintahKerja(string $organisasiId, bool $tulis): array
    {
        $ringkasan = ['Kosong' => 0, 'Diisi' => 0, 'PerUnit' => []];

        DB::table('PerintahKerja')
            ->where('OrganisasiId', $organisasiId)
            ->whereNull('UnitPengelolaId')
            ->whereNull('DihapusPada')
            ->select(['Id', 'KeluhanId'])
            ->chunkById(self::UKURAN_POTONGAN, function (Collection $potongan) use ($organisasiId, $tulis, &$ringkasan): void {
                $ids = array_values($potongan->map(fn (object $baris): string => $this->teks($baris->Id))->all());
                $keluhan = $this->keluhanAsal(array_values(array_unique(array_filter(
                    $potongan->map(fn (object $baris): ?string => $this->teksAtauNull($baris->KeluhanId))->all(),
                ))));
                $asetUtama = $this->asetUtama($organisasiId, $ids);
                $unitRencana = $this->unitRencanaAsal($organisasiId, $ids);

                $turunan = [];

                foreach ($potongan as $baris) {
                    $id = $this->teks($baris->Id);

                    $turunan[$id] = $this->penentu->untukPerintahKerja(
                        null,
                        $keluhan->get($this->teks($baris->KeluhanId)),
                        $asetUtama[$id] ?? null,
                        $unitRencana[$id] ?? null,
                    );
                }

                $this->terapkan('PerintahKerja', $organisasiId, $turunan, $tulis, $ringkasan);
            }, 'Id');

        return $ringkasan;
    }

    /**
     * Keluhan asal potongan ini, dengan unit pengelola efektifnya.
     *
     * Keluhan yang masih kosong diberi unit turunannya di memori (tidak
     * disimpan dari sini), supaya pratinjau menghasilkan angka yang sama
     * dengan penerapan, yang mengisi keluhan lebih dulu.
     *
     * @param  list<string>  $keluhanIds
     * @return Collection<string, Keluhan>
     */
    private function keluhanAsal(array $keluhanIds): Collection
    {
        if ($keluhanIds === []) {
            return new Collection;
        }

        return Keluhan::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->withTrashed()
            ->whereIn('Id', $keluhanIds)
            ->get(['Id', 'UnitPengelolaId', 'KategoriKeluhanId', 'AsetId'])
            ->each(function (Keluhan $satu): void {
                if (! filled($satu->UnitPengelolaId)) {
                    $satu->UnitPengelolaId = $this->unitKeluhan($satu->KategoriKeluhanId, $satu->AsetId);
                }
            })
            ->keyBy('Id');
    }

    /**
     * Aset utama tiap perintah kerja; bila tidak ada yang bertanda utama, aset pertama.
     *
     * @param  list<string>  $perintahKerjaIds
     * @return array<string, string>
     */
    private function asetUtama(string $organisasiId, array $perintahKerjaIds): array
    {
        $hasil = [];

        $baris = DB::table('PerintahKerjaAset')
            ->where('OrganisasiId', $organisasiId)
            ->whereIn('PerintahKerjaId', $perintahKerjaIds)
            ->orderByDesc('Utama')
            ->orderBy('Id')
            ->get(['PerintahKerjaId', 'AsetId']);

        foreach ($baris as $satu) {
            $hasil[$this->teks($satu->PerintahKerjaId)] ??= $this->teks($satu->AsetId);
        }

        return $hasil;
    }

    /**
     * Unit pengelola efektif rencana asal tiap perintah kerja: jadwal preventif
     * lebih dulu, lalu pelaksanaan kalibrasi.
     *
     * @param  list<string>  $perintahKerjaIds
     * @return array<string, string>
     */
    private function unitRencanaAsal(string $organisasiId, array $perintahKerjaIds): array
    {
        $hasil = [];

        $preventif = DB::table('JadwalPemeliharaan as jp')
            ->join('RencanaPemeliharaanAset as rpa', 'rpa.Id', '=', 'jp.RencanaPemeliharaanAsetId')
            ->join('RencanaPemeliharaan as rp', 'rp.Id', '=', 'rpa.RencanaPemeliharaanId')
            ->where('jp.OrganisasiId', $organisasiId)
            ->whereIn('jp.PerintahKerjaId', $perintahKerjaIds)
            ->orderBy('jp.Id')
            ->get(['jp.PerintahKerjaId', 'rp.Id as RencanaId', 'rp.UnitPengelolaId']);

        foreach ($preventif as $satu) {
            $unit = $this->teksAtauNull($satu->UnitPengelolaId) ?? ($this->unitRencanaPemeliharaan[$this->teks($satu->RencanaId)] ?? null);

            if ($unit !== null) {
                $hasil[$this->teks($satu->PerintahKerjaId)] ??= $unit;
            }
        }

        $kalibrasi = DB::table('PelaksanaanKalibrasi as pk')
            ->join('RencanaKalibrasi as rk', 'rk.Id', '=', 'pk.RencanaKalibrasiId')
            ->where('pk.OrganisasiId', $organisasiId)
            ->whereIn('pk.PerintahKerjaId', $perintahKerjaIds)
            ->orderBy('pk.Id')
            ->get(['pk.PerintahKerjaId', 'rk.Id as RencanaId', 'rk.UnitPengelolaId']);

        foreach ($kalibrasi as $satu) {
            $unit = $this->teksAtauNull($satu->UnitPengelolaId) ?? ($this->unitRencanaKalibrasi[$this->teks($satu->RencanaId)] ?? null);

            if ($unit !== null) {
                $hasil[$this->teks($satu->PerintahKerjaId)] ??= $unit;
            }
        }

        return $hasil;
    }

    /**
     * Sama dengan PenentuUnitPengelola::untukKeluhan($kategori, $aset), dipecah
     * menjadi bagian kategori lalu bagian aset supaya tiap kategori dan tiap
     * aset cukup ditanyakan sekali per jalan.
     */
    private function unitKeluhan(?string $kategoriKeluhanId, ?string $asetId): ?string
    {
        if (filled($kategoriKeluhanId)) {
            if (! array_key_exists($kategoriKeluhanId, $this->memoKategori)) {
                $this->memoKategori[$kategoriKeluhanId] = $this->penentu->untukKeluhan($kategoriKeluhanId, null);
            }

            if ($this->memoKategori[$kategoriKeluhanId] !== null) {
                return $this->memoKategori[$kategoriKeluhanId];
            }
        }

        return $this->unitAset($asetId);
    }

    private function unitAset(?string $asetId): ?string
    {
        if (! filled($asetId)) {
            return null;
        }

        if (! array_key_exists($asetId, $this->memoAset)) {
            if (count($this->memoAset) >= self::BATAS_MEMO_ASET) {
                $this->memoAset = [];
            }

            $this->memoAset[$asetId] = $this->penentu->untukKeluhan(null, $asetId);
        }

        return $this->memoAset[$asetId];
    }

    /**
     * Menulis satu potongan (bila diminta) dan menambah ringkasannya.
     *
     * Ditulis per unit tujuan dalam satu transaksi bersama audit-nya. Syarat
     * `UnitPengelolaId IS NULL` diulang di UPDATE supaya nilai yang diisi orang
     * lain di antara baca dan tulis tidak pernah tertimpa.
     *
     * @param  array<string, ?string>  $turunan  Id baris => unit turunan (null = tidak dapat diturunkan)
     * @param  array{Kosong: int, Diisi: int, PerUnit: array<string, int>}  $ringkasan
     */
    private function terapkan(string $tabel, string $organisasiId, array $turunan, bool $tulis, array &$ringkasan): void
    {
        $ringkasan['Kosong'] += count($turunan);

        $perUnit = [];

        foreach ($turunan as $id => $unit) {
            if ($unit !== null) {
                $perUnit[$unit][] = $id;
            }
        }

        foreach ($perUnit as $unit => $ids) {
            $jumlah = count($ids);

            if ($tulis) {
                $jumlah = $this->tulis($tabel, $organisasiId, (string) $unit, $ids);
            }

            $ringkasan['Diisi'] += $jumlah;
            $ringkasan['PerUnit'][$unit] = ($ringkasan['PerUnit'][$unit] ?? 0) + $jumlah;
        }
    }

    /**
     * @param  list<string>  $ids
     * @return int Jumlah baris yang benar-benar terisi
     */
    private function tulis(string $tabel, string $organisasiId, string $unit, array $ids): int
    {
        $terisi = 0;

        $this->transaksi->jalankan(function () use ($tabel, $organisasiId, $unit, $ids, &$terisi): void {
            $terisi = DB::table($tabel)
                ->where('OrganisasiId', $organisasiId)
                ->whereIn('Id', $ids)
                ->whereNull('UnitPengelolaId')
                ->update(['UnitPengelolaId' => $unit]);

            if ($terisi > 0) {
                $this->audit->catat(
                    self::AKSI_AUDIT,
                    $tabel,
                    null,
                    dataSebelum: ['UnitPengelolaId' => null],
                    dataSesudah: ['UnitPengelolaId' => $unit, 'Jumlah' => $terisi, 'Id' => $ids],
                );
            }
        });

        return $terisi;
    }

    private function teks(mixed $nilai): string
    {
        return is_scalar($nilai) ? (string) $nilai : '';
    }

    private function teksAtauNull(mixed $nilai): ?string
    {
        return is_scalar($nilai) && (string) $nilai !== '' ? (string) $nilai : null;
    }
}
