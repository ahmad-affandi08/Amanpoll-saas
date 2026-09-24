<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\Services;

use App\Core\Izin\ScopeLingkup;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusKeluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Penyusun data layar Pelapor Mode Lapangan (DESIGN §36.7). Hanya membaca: seluruh
 * kueri model tetap melewati tenancy dan `ScopeLingkup`, dan daftar laporan selalu
 * disaring ke keluhan milik pelapor sendiri oleh pemanggil.
 *
 * Bentuk baris yang dihasilkan mengikuti tipe `// Pelapor (D)` di
 * `resources/js/features/Lapangan/types.ts`.
 */
final class PenyusunLayarPelapor
{
    /** Batas pilihan lokasi di lembar "Ganti lokasi". */
    public const MAKS_LOKASI = 300;

    /** Batas aset satu lokasi (beserta ruangan di bawahnya). */
    public const MAKS_ASET = 100;

    /** Status keluhan yang masih ditangani; Selesai tidak termasuk karena perbaikannya sudah tuntas. */
    public const STATUS_TERBUKA = [
        StatusKeluhan::Baru,
        StatusKeluhan::Ditinjau,
        StatusKeluhan::Diterima,
        StatusKeluhan::Diproses,
    ];

    /**
     * Lokasi kerja pelapor: ruangan dari penugasan perannya yang masih berlaku.
     * Pengguna tanpa ruangan (tanpa batas lingkup) memilih sendiri lewat "Ganti".
     */
    public function lokasiSaya(Pengguna $pengguna): ?Lokasi
    {
        $lokasiId = DB::table('PenggunaPeran')
            ->where('OrganisasiId', $pengguna->OrganisasiId)
            ->where('PenggunaId', $pengguna->Id)
            ->whereNotNull('LokasiId')
            ->where(fn ($q) => $q->whereNull('BerlakuMulai')->orWhere('BerlakuMulai', '<=', now()))
            ->where(fn ($q) => $q->whereNull('BerlakuSampai')->orWhere('BerlakuSampai', '>=', now()))
            ->orderBy('Id')
            ->value('LokasiId');

        return is_string($lokasiId) ? $this->cariLokasi($lokasiId) : null;
    }

    /** Lokasi dalam lingkup pengguna, atau null bila tidak ada/di luar lingkup. */
    public function cariLokasi(?string $lokasiId): ?Lokasi
    {
        if ($lokasiId === null || $lokasiId === '') {
            return null;
        }

        return Lokasi::query()
            ->with(['induk' => fn ($q) => $q->withoutGlobalScope(ScopeLingkup::class)])
            ->find($lokasiId);
    }

    /** "Menara A · Lt. 12": nama induk (bila ada) dan nama lokasinya. */
    public function labelLokasi(?Lokasi $lokasi): ?string
    {
        if ($lokasi === null) {
            return null;
        }

        $induk = $lokasi->relationLoaded('induk') ? $lokasi->getRelation('induk') : null;

        return $induk instanceof Lokasi ? "{$induk->Nama} · {$lokasi->Nama}" : $lokasi->Nama;
    }

    /** @return array{Id: string, Nama: string, Label: string}|null */
    public function ringkasLokasi(?Lokasi $lokasi): ?array
    {
        if ($lokasi === null) {
            return null;
        }

        return ['Id' => $lokasi->Id, 'Nama' => $lokasi->Nama, 'Label' => (string) $this->labelLokasi($lokasi)];
    }

    /**
     * Pilihan lokasi di lembar "Ganti", hanya yang ada di lingkup pengguna.
     *
     * @return list<array{Id: string, Nama: string, Label: string}>
     */
    public function pilihanLokasi(): array
    {
        return array_values(Lokasi::query()
            ->with(['induk' => fn ($q) => $q->withoutGlobalScope(ScopeLingkup::class)])
            ->where('Status', 'Aktif')
            ->orderBy('Nama')
            ->orderBy('Id')
            ->limit(self::MAKS_LOKASI)
            ->get()
            ->map(fn (Lokasi $lokasi): array => ['Id' => $lokasi->Id, 'Nama' => $lokasi->Nama, 'Label' => (string) $this->labelLokasi($lokasi)])
            ->all());
    }

    /**
     * Aset di lokasi itu dan ruangan tepat di bawahnya, masing-masing dengan
     * penanda laporan terbuka (pencegah laporan ganda, layar 04 dan 14).
     *
     * @return list<array<string, mixed>>
     */
    public function asetDiLokasi(Lokasi $lokasi, Pengguna $pengguna): array
    {
        $lokasiId = Lokasi::query()->where('IndukId', $lokasi->Id)->pluck('Id')->push($lokasi->Id)->all();

        $aset = Aset::query()
            ->with(['kategoriAset', 'lokasi' => fn ($q) => $q->with(['induk' => fn ($induk) => $induk->withoutGlobalScope(ScopeLingkup::class)])])
            ->whereIn('LokasiId', $lokasiId)
            ->whereNotIn('Status', ['Diarsipkan'])
            ->orderBy('Nama')
            ->orderBy('Id')
            ->limit(self::MAKS_ASET)
            ->get();

        return $this->ringkasDaftarAset($aset, $pengguna);
    }

    /**
     * @param  Collection<int, Aset>  $aset
     * @return list<array<string, mixed>>
     */
    public function ringkasDaftarAset(Collection $aset, Pengguna $pengguna): array
    {
        $terbuka = $this->laporanTerbukaUntukAset(array_values($aset->map(fn (Aset $satu): string => $satu->Id)->all()), $pengguna);

        return array_values($aset->map(fn (Aset $satu): array => $this->ringkasAset($satu, $terbuka[$satu->Id] ?? []))->all());
    }

    /**
     * @param  list<array<string, mixed>>  $laporanTerbuka
     * @return array<string, mixed>
     */
    public function ringkasAset(Aset $aset, array $laporanTerbuka): array
    {
        $kategori = $aset->relationLoaded('kategoriAset') ? $aset->getRelation('kategoriAset') : null;
        $lokasi = $aset->relationLoaded('lokasi') ? $aset->getRelation('lokasi') : null;

        return [
            'Id' => $aset->Id,
            'KodeAset' => $aset->KodeAset,
            'Nama' => $aset->Nama,
            'Kategori' => $kategori?->getAttribute('Nama'),
            'Kondisi' => $aset->Kondisi,
            'Status' => $aset->Status,
            'LokasiId' => $aset->LokasiId,
            'LokasiNama' => $lokasi?->getAttribute('Nama'),
            'LokasiLabel' => $lokasi instanceof Lokasi ? $this->labelLokasi($lokasi) : null,
            'LaporanTerbuka' => $laporanTerbuka,
        ];
    }

    /**
     * Keluhan terbuka per aset. Keluhan orang lain hanya disebut nomor, judul, dan
     * statusnya (tanpa nama pelapor/teknisi) — cukup untuk mencegah laporan ganda
     * tanpa membuka isi keluhan yang bukan miliknya.
     *
     * @param  list<string>  $asetId
     * @return array<string, list<array<string, mixed>>>
     */
    public function laporanTerbukaUntukAset(array $asetId, Pengguna $pengguna): array
    {
        if ($asetId === []) {
            return [];
        }

        $keluhan = Keluhan::query()
            ->whereIn('AsetId', $asetId)
            ->whereIn('Status', array_map(fn (StatusKeluhan $status): string => $status->value, self::STATUS_TERBUKA))
            ->latest('DilaporkanPada')
            ->orderBy('Id')
            ->get(['Id', 'AsetId', 'Nomor', 'Judul', 'Status', 'PelaporId', 'DilaporkanPada']);

        $milikSaya = $keluhan->where('PelaporId', $pengguna->Id);
        $teknisi = $this->teknisiUntuk(array_values($milikSaya->map(fn (Keluhan $satu): string => $satu->Id)->all()));

        $hasil = [];
        foreach ($keluhan as $satu) {
            $hasil[(string) $satu->AsetId][] = [
                'Id' => $satu->Id,
                'Nomor' => $satu->Nomor,
                'Judul' => $satu->Judul,
                'Status' => $satu->Status,
                'MilikSaya' => $satu->PelaporId === $pengguna->Id,
                'NamaTeknisi' => $teknisi[$satu->Id]['Nama'] ?? null,
            ];
        }

        return $hasil;
    }

    /**
     * Teknisi yang sedang memegang perintah kerja dari keluhan-keluhan itu.
     *
     * Dibaca tanpa `ScopeLingkup` karena pelapor hanya perlu nama dan nomor yang
     * bisa dihubungi untuk keluhannya sendiri; pemanggil yang memastikan keluhannya
     * milik pelapor. Tenancy tetap berlaku.
     *
     * @param  list<string>  $keluhanId
     * @return array<string, array{Nama: string, Telepon: string|null, Jabatan: string|null, StatusPekerjaan: string, DitugaskanPada: string|null, DimulaiPada: string|null, Ringkasan: string|null}>
     */
    public function teknisiUntuk(array $keluhanId): array
    {
        if ($keluhanId === []) {
            return [];
        }

        $perintahKerja = PerintahKerja::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->with(['penugasan.pengguna'])
            ->whereIn('KeluhanId', $keluhanId)
            ->where('Status', '!=', StatusPerintahKerja::Dibatalkan->value)
            ->latest('DibuatPada')
            ->orderBy('Id')
            ->get();

        $hasil = [];
        $aktif = [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value, StatusPenugasanPerintahKerja::Selesai->value];

        foreach ($perintahKerja as $satu) {
            $keluhan = (string) $satu->KeluhanId;
            if (isset($hasil[$keluhan])) {
                continue;
            }

            $penugasan = $satu->penugasan->first(fn ($p): bool => in_array($p->Status, $aktif, true) && $p->pengguna !== null);
            $orang = $penugasan?->pengguna;

            if ($penugasan === null || ! $orang instanceof Pengguna) {
                continue;
            }

            $hasil[$keluhan] = [
                'Nama' => $orang->Nama,
                'Telepon' => $orang->Telepon,
                'Jabatan' => $orang->Jabatan,
                'StatusPekerjaan' => $satu->Status,
                'DitugaskanPada' => $this->waktu($penugasan->DitugaskanPada),
                'DimulaiPada' => $this->waktu($satu->DimulaiPada),
                'Ringkasan' => $satu->RingkasanPenyelesaian,
            ];
        }

        return $hasil;
    }

    /**
     * Baris daftar laporan (beranda, Laporan Saya). Pemanggil wajib sudah menyaring
     * `PelaporId` ke pengguna yang sedang masuk.
     *
     * @param  Collection<int, Keluhan>  $keluhan
     * @return list<array<string, mixed>>
     */
    public function ringkasDaftarLaporan(Collection $keluhan): array
    {
        $keluhan->loadMissing(['aset.kategoriAset', 'kategoriKeluhan', 'lokasi']);
        $id = array_values($keluhan->map(fn (Keluhan $satu): string => $satu->Id)->all());
        $teknisi = $this->teknisiUntuk($id);
        $statusSejak = $id === [] ? collect() : RiwayatStatusKeluhan::query()
            ->whereIn('KeluhanId', $id)
            ->groupBy('KeluhanId')
            ->selectRaw('KeluhanId, MAX(DiubahPada) as Terakhir')
            ->pluck('Terakhir', 'KeluhanId');

        return array_values($keluhan->map(fn (Keluhan $satu): array => [
            ...$this->ringkasLaporan($satu),
            'StatusSejak' => $this->waktuMentah($statusSejak[$satu->Id] ?? null),
            'Teknisi' => $teknisi[$satu->Id] ?? null,
        ])->all());
    }

    /** @return array<string, mixed> */
    public function ringkasLaporan(Keluhan $keluhan): array
    {
        $aset = $keluhan->relationLoaded('aset') ? $keluhan->getRelation('aset') : null;
        $kategoriAset = $aset instanceof Aset && $aset->relationLoaded('kategoriAset') ? $aset->getRelation('kategoriAset') : null;
        $kategori = $keluhan->relationLoaded('kategoriKeluhan') ? $keluhan->getRelation('kategoriKeluhan') : null;
        $lokasi = $keluhan->relationLoaded('lokasi') ? $keluhan->getRelation('lokasi') : null;

        return [
            'Id' => $keluhan->Id,
            'Nomor' => $keluhan->Nomor,
            'Judul' => $keluhan->Judul,
            'Deskripsi' => $keluhan->Deskripsi,
            'Status' => $keluhan->Status,
            'Versi' => $keluhan->Versi,
            'KategoriNama' => $kategori?->getAttribute('Nama'),
            'Aset' => $aset instanceof Aset ? [
                'Id' => $aset->Id,
                'KodeAset' => $aset->KodeAset,
                'Nama' => $aset->Nama,
                'Kategori' => $kategoriAset?->getAttribute('Nama'),
            ] : null,
            'LokasiNama' => $lokasi?->getAttribute('Nama'),
            'DilaporkanPada' => $this->waktu($keluhan->DilaporkanPada),
            'BatasResponsPada' => $this->waktu($keluhan->BatasResponsPada),
            'BatasPenyelesaianPada' => $this->waktu($keluhan->BatasPenyelesaianPada),
            'DiresolusikanPada' => $this->waktu($keluhan->DiresolusikanPada),
            'DitutupPada' => $this->waktu($keluhan->DitutupPada),
            'Rating' => $keluhan->Rating === null ? null : (int) $keluhan->Rating,
            'Ulasan' => $keluhan->Ulasan,
        ];
    }

    /**
     * Riwayat status keluhan untuk perhentian "Perjalanan laporan" (layar 10).
     *
     * @return list<array{StatusSebelum: string|null, StatusSesudah: string, Catatan: string|null, DiubahPada: string|null, NamaPengubah: string|null, OlehSaya: bool}>
     */
    public function riwayat(Keluhan $keluhan, Pengguna $pengguna): array
    {
        return array_values(RiwayatStatusKeluhan::query()
            ->with('diubahOleh')
            ->where('KeluhanId', $keluhan->Id)
            ->oldest('DiubahPada')
            ->orderBy('Id')
            ->get()
            ->map(fn (RiwayatStatusKeluhan $satu): array => [
                'StatusSebelum' => $satu->StatusSebelum,
                'StatusSesudah' => (string) $satu->StatusSesudah,
                'Catatan' => $satu->Catatan,
                'DiubahPada' => $this->waktuMentah($satu->DiubahPada),
                'NamaPengubah' => $satu->diubahOleh?->Nama,
                'OlehSaya' => $satu->DiubahOleh === $pengguna->Id,
            ])
            ->all());
    }

    /**
     * Isi layar Pantau laporan rekan (PRD 8.20): hanya nomor, judul, alat/lokasi,
     * status, dan jam tiap perubahan status. Nama pelapor, nama teknisi, catatan
     * riwayat, deskripsi, dan foto sengaja tidak dibaca sama sekali, supaya tidak
     * ada jalan bocor lewat props. Pemanggil wajib sudah memeriksa `KeluhanPolicy::pantau`.
     *
     * @return array{laporan: array{Nomor: string, Judul: string, Status: string, Aset: array{KodeAset: string, Nama: string, Kategori: string|null}|null, LokasiLabel: string|null}, riwayat: list<array{Status: string, Pada: string|null}>}
     */
    public function pantauan(Keluhan $keluhan): array
    {
        $aset = $keluhan->AsetId === null ? null : Aset::query()->with('kategoriAset')->find($keluhan->AsetId);
        $kategoriAset = $aset?->relationLoaded('kategoriAset') ? $aset->getRelation('kategoriAset') : null;

        return [
            'laporan' => [
                'Nomor' => (string) $keluhan->Nomor,
                'Judul' => (string) $keluhan->Judul,
                'Status' => (string) $keluhan->Status,
                'Aset' => $aset === null ? null : [
                    'KodeAset' => (string) $aset->KodeAset,
                    'Nama' => (string) $aset->Nama,
                    'Kategori' => $kategoriAset?->getAttribute('Nama'),
                ],
                'LokasiLabel' => $this->labelLokasi($this->cariLokasi($keluhan->LokasiId)),
            ],
            // Hanya perubahan status: baris pengalihan unit pengelola (status sebelum = sesudah)
            // adalah urusan internal antrean dan tampil sebagai langkah status ganda.
            'riwayat' => array_values(RiwayatStatusKeluhan::query()
                ->where('KeluhanId', $keluhan->Id)
                ->where(fn ($kueri) => $kueri->whereNull('StatusSebelum')->orWhereColumn('StatusSebelum', '!=', 'StatusSesudah'))
                ->oldest('DiubahPada')
                ->orderBy('Id')
                ->get(['StatusSesudah', 'DiubahPada'])
                ->map(fn (RiwayatStatusKeluhan $satu): array => [
                    'Status' => (string) $satu->StatusSesudah,
                    'Pada' => $this->waktuMentah($satu->DiubahPada),
                ])
                ->all()),
        ];
    }

    private function waktu(?CarbonInterface $waktu): ?string
    {
        return $waktu?->toIso8601String();
    }

    private function waktuMentah(mixed $nilai): ?string
    {
        if ($nilai instanceof CarbonInterface) {
            return $nilai->toIso8601String();
        }

        return is_string($nilai) && $nilai !== '' ? CarbonImmutable::parse($nilai, 'UTC')->toIso8601String() : null;
    }
}
