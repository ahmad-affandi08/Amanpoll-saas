<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\Services;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusGaransiAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Menyusun data tiket untuk layar Teknisi Mode Lapangan (DESIGN §36.6).
 *
 * Hanya membaca. Status, SLA, dan hak akses tetap milik domain Pemeliharaan:
 * "tiket saya" memakai syarat yang sama dengan `PerintahKerjaPolicy::ditugaskan`
 * (penugasan aktif berstatus Ditugaskan atau Diterima), dan transisi yang
 * ditawarkan disaring lewat `Gate` seperti halaman dasbor.
 */
final class PenyusunLayarTeknisi
{
    public function __construct(private readonly KalenderOrganisasi $kalender) {}

    /** Status tiket yang masih dikerjakan teknisi. */
    public const STATUS_AKTIF = [
        StatusPerintahKerja::Ditugaskan,
        StatusPerintahKerja::Diterima,
        StatusPerintahKerja::Dikerjakan,
        StatusPerintahKerja::MenungguSukuCadang,
        StatusPerintahKerja::MenungguPenyedia,
        StatusPerintahKerja::Dijeda,
    ];

    /** Status sesudah teknisi menyelesaikan bagiannya. */
    public const STATUS_SELESAI = [
        StatusPerintahKerja::MenungguVerifikasi,
        StatusPerintahKerja::Selesai,
        StatusPerintahKerja::Ditutup,
    ];

    /** Batas tiket yang dibawa ke satu layar; sama dengan paket offline. */
    public const BATAS_TIKET = 50;

    /** Batas riwayat tiket selesai di tab "Selesai". */
    public const BATAS_SELESAI = 30;

    /**
     * Tiket yang sedang ditugaskan kepada pengguna ini.
     *
     * @return Builder<PerintahKerja>
     */
    public function kueriDitugaskan(Pengguna $pengguna): Builder
    {
        return PerintahKerja::query()->whereHas('penugasan', fn (Builder $penugasan) => $penugasan
            ->where('PenggunaId', $pengguna->Id)
            ->whereIn('Status', [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value]));
    }

    /**
     * Tiket aktif milik teknisi, yang kritis dan paling mendesak lebih dulu.
     *
     * @return list<array<string, mixed>>
     */
    public function tiketAktif(Pengguna $pengguna): array
    {
        $tiket = $this->muatRelasi($this->kueriDitugaskan($pengguna), $pengguna)
            ->whereIn('Status', $this->nilai(self::STATUS_AKTIF))
            ->orderByRaw("CASE WHEN Prioritas = 'Kritis' THEN 0 ELSE 1 END")
            ->orderByRaw('COALESCE(BatasPenyelesaianPada, DijadwalkanSelesaiPada, DijadwalkanMulaiPada) IS NULL')
            ->orderByRaw('COALESCE(BatasPenyelesaianPada, DijadwalkanSelesaiPada, DijadwalkanMulaiPada)')
            ->orderBy('Id')
            ->limit(self::BATAS_TIKET)
            ->get();

        return $this->ringkasSemua($tiket, $pengguna);
    }

    /**
     * Tiket yang baru saja diselesaikan teknisi ini (tab "Selesai" dan jadwal hari ini).
     *
     * Penugasan yang sudah ditutup koordinator ikut dihitung supaya riwayatnya
     * tidak hilang, tetapi hanya tiket yang masih boleh dibuka (policy `view`)
     * yang diberi tautan.
     *
     * @return list<array<string, mixed>>
     */
    public function tiketSelesai(Pengguna $pengguna): array
    {
        $kueri = PerintahKerja::query()->whereHas('penugasan', fn (Builder $penugasan) => $penugasan
            ->where('PenggunaId', $pengguna->Id)
            ->whereIn('Status', [StatusPenugasanPerintahKerja::Diterima->value, StatusPenugasanPerintahKerja::Selesai->value]));

        $tiket = $this->muatRelasi($kueri, $pengguna)
            ->whereIn('Status', $this->nilai(self::STATUS_SELESAI))
            ->where('DiperbaruiPada', '>=', now()->subDays(14))
            ->orderByDesc('DiperbaruiPada')
            ->orderByDesc('Id')
            ->limit(self::BATAS_SELESAI)
            ->get();

        return $this->ringkasSemua($tiket, $pengguna);
    }

    /**
     * @param  Builder<PerintahKerja>  $kueri
     * @return Builder<PerintahKerja>
     */
    public function muatRelasi(Builder $kueri, Pengguna $pengguna): Builder
    {
        return $kueri->with([
            'keluhan:Id,Nomor,DilaporkanPada',
            'lokasi:Id,Nama,IndukId',
            'lokasi.induk:Id,Nama',
            'aset',
            'aset.lokasi:Id,Nama,IndukId',
            'aset.lokasi.induk:Id,Nama',
            'aset.kategoriAset:Id,Nama',
            'penugasan' => fn ($penugasan) => $penugasan->where('PenggunaId', $pengguna->Id),
        ]);
    }

    /**
     * @param  Collection<int, PerintahKerja>  $tiket
     * @return list<array<string, mixed>>
     */
    public function ringkasSemua(Collection $tiket, Pengguna $pengguna): array
    {
        return array_values($tiket->map(fn (PerintahKerja $satu): array => $this->ringkas($satu, $pengguna))->all());
    }

    /**
     * Bentuk `TiketTeknisi` di `features/Lapangan/types.ts`.
     *
     * @return array<string, mixed>
     */
    public function ringkas(PerintahKerja $perintahKerja, Pengguna $pengguna): array
    {
        $penugasan = $perintahKerja->penugasan->first(fn (PenugasanPerintahKerja $satu): bool => $satu->PenggunaId === $pengguna->Id);
        $aset = $this->asetUtama($perintahKerja);
        $lokasi = $perintahKerja->lokasi ?? $aset?->lokasi;

        return [
            'Id' => $perintahKerja->Id,
            'Nomor' => $perintahKerja->Nomor,
            'Judul' => $perintahKerja->Judul,
            'Jenis' => $perintahKerja->Jenis,
            'Status' => $perintahKerja->Status,
            'Prioritas' => $perintahKerja->Prioritas,
            'Versi' => $perintahKerja->Versi,
            'DariKeluhan' => $perintahKerja->KeluhanId !== null,
            'DilaporkanPada' => ($perintahKerja->keluhan->DilaporkanPada ?? $perintahKerja->DibuatPada)->toIso8601String(),
            'DijadwalkanMulaiPada' => $perintahKerja->DijadwalkanMulaiPada?->toIso8601String(),
            'BatasPada' => ($perintahKerja->BatasPenyelesaianPada ?? $perintahKerja->DijadwalkanSelesaiPada)?->toIso8601String(),
            'DimulaiPada' => $perintahKerja->DimulaiPada?->toIso8601String(),
            'DiperbaruiPada' => $perintahKerja->DiperbaruiPada->toIso8601String(),
            'PenugasanId' => $penugasan?->Id,
            'PerluRespons' => $penugasan?->Status === StatusPenugasanPerintahKerja::Ditugaskan->value,
            'DitugaskanPada' => $penugasan?->DitugaskanPada?->toIso8601String(),
            'DapatDibuka' => Gate::forUser($pengguna)->allows('view', $perintahKerja),
            'Aset' => $aset === null ? null : $this->ringkasAset($aset),
            'Lokasi' => $lokasi === null ? null : $this->ringkasLokasi($lokasi),
        ];
    }

    /**
     * Status tujuan yang boleh dipilih pengguna ini, disaring policy seperti dasbor.
     *
     * @return list<string>
     */
    public function statusTujuan(PerintahKerja $perintahKerja, Pengguna $pengguna): array
    {
        $tujuan = StatusPerintahKerja::from($perintahKerja->Status)->tujuanYangDiizinkan();

        return array_values(array_filter(
            array_map(fn (StatusPerintahKerja $status): string => $status->value, $tujuan),
            fn (string $status): bool => Gate::forUser($pengguna)->allows('ubahStatus', [$perintahKerja, $status]),
        ));
    }

    /** @return array<string, mixed> */
    public function ringkasAset(Aset $aset): array
    {
        return [
            'Id' => $aset->Id,
            'KodeAset' => $aset->KodeAset,
            'Nama' => $aset->Nama,
            'Kategori' => $aset->kategoriAset?->Nama,
            'Kondisi' => $aset->Kondisi,
            'Lokasi' => $aset->lokasi === null ? null : $this->ringkasLokasi($aset->lokasi),
        ];
    }

    /** @return array{Nama: string, Induk: string|null} */
    public function ringkasLokasi(Lokasi $lokasi): array
    {
        return [
            'Nama' => $lokasi->Nama,
            'Induk' => $lokasi->induk?->Nama,
        ];
    }

    /** Aset utama pekerjaan; bila tidak ada yang ditandai utama, aset pertama. */
    public function asetUtama(PerintahKerja $perintahKerja): ?Aset
    {
        return $perintahKerja->aset->first(fn (Aset $aset): bool => (bool) $aset->getAttribute('pivot')?->Utama)
            ?? $perintahKerja->aset->first();
    }

    /**
     * Aset hasil pindai atau pilihan daftar aset (layar 14), dengan aksi cepat sesuai izin.
     *
     * Bentuk `AsetDitemukan` di `features/Lapangan/types.ts`.
     *
     * @return array<string, mixed>
     */
    public function detailAset(Aset $aset, Pengguna $pengguna): array
    {
        $aset->loadMissing(['kategoriAset:Id,Nama', 'lokasi:Id,Nama,IndukId', 'lokasi.induk:Id,Nama', 'modelAset:Id,Nama,MerekId', 'modelAset.merek:Id,Nama']);
        $gerbang = Gate::forUser($pengguna);

        $tiketSaya = $this->muatRelasi($this->kueriDitugaskan($pengguna), $pengguna)
            ->whereIn('Status', $this->nilai(self::STATUS_AKTIF))
            ->whereHas('asetPekerjaan', fn (Builder $kueri) => $kueri->where('AsetId', $aset->Id))
            ->orderByRaw("CASE WHEN Prioritas = 'Kritis' THEN 0 ELSE 1 END")
            ->orderByRaw('BatasPenyelesaianPada IS NULL')
            ->orderBy('BatasPenyelesaianPada')
            ->first();

        $inspeksi = Inspeksi::query()
            ->where('AsetId', $aset->Id)
            ->where('Status', '!=', 'Selesai')
            ->orderBy('DijadwalkanPada')
            ->orderBy('Id')
            ->get()
            ->first(fn (Inspeksi $satu): bool => $gerbang->allows('update', $satu));

        $garansi = GaransiAset::query()
            ->where('AsetId', $aset->Id)
            ->where('Status', StatusGaransiAset::Aktif->value)
            ->max('BerakhirPada');

        $merek = $aset->modelAset?->merek?->Nama;
        $model = $aset->modelAset?->Nama;

        return [
            ...$this->ringkasAset($aset),
            'Status' => $aset->Status,
            'TingkatKritis' => $aset->TingkatKritis,
            'MerekTipe' => trim(($merek ?? '').' '.($model ?? '')) ?: null,
            'GaransiBerakhirPada' => $this->iso($garansi),
            'ServisTerakhirPada' => $this->iso($aset->perintahKerja()->whereNotNull('DiselesaikanPada')->max('DiselesaikanPada')),
            'TiketSaya' => $tiketSaya === null ? null : $this->ringkas($tiketSaya, $pengguna),
            'Inspeksi' => $inspeksi === null ? null : [
                'Id' => $inspeksi->Id,
                'Nomor' => $inspeksi->Nomor,
                'DijadwalkanPada' => $inspeksi->DijadwalkanPada?->toIso8601String(),
            ],
            'BolehLapor' => $gerbang->allows('create', Keluhan::class),
            'BolehLihatRiwayat' => $gerbang->allows('view', $aset),
        ];
    }

    /**
     * Ringkasan angka dan garis waktu pekerjaan satu aset (layar 15).
     *
     * @return array{ringkasan: array<string, mixed>, linimasa: list<array<string, mixed>>}
     */
    public function riwayatAset(Aset $aset): array
    {
        // Awal tahun menurut kalender organisasi, bukan jam UTC server.
        $awalTahun = $this->kalender->awalHari($this->kalender->hariIni()->startOfYear());
        $tigaPuluhHari = now()->subDays(30);

        $menitHenti = (int) WaktuHentiAset::query()
            ->where('AsetId', $aset->Id)
            ->where('MulaiPada', '>=', $tigaPuluhHari)
            ->sum('DurasiMenit');

        $kerusakan = $aset->perintahKerja()
            ->where('Jenis', 'Korektif')
            ->where('PerintahKerja.DibuatPada', '>=', now()->subYear())
            ->pluck('PerintahKerja.DibuatPada')
            ->map(fn (mixed $waktu): CarbonImmutable => CarbonImmutable::parse((string) $waktu))
            // Relasinya sudah berurutan terbaru dulu; jeda dihitung dari yang terlama.
            ->sort()
            ->values();

        $jeda = [];
        for ($i = 1; $i < $kerusakan->count(); $i++) {
            $jeda[] = abs($kerusakan[$i - 1]->diffInHours($kerusakan[$i])) / 24;
        }

        $pekerjaan = $aset->perintahKerja()
            ->with(['penugasan.pengguna:Id,Nama'])
            ->orderByRaw('COALESCE(PerintahKerja.DiselesaikanPada, PerintahKerja.DimulaiPada, PerintahKerja.DibuatPada) DESC')
            ->limit(self::BATAS_SELESAI)
            ->get();

        $durasi = WaktuKerja::query()
            ->whereIn('PerintahKerjaId', $pekerjaan->pluck('Id'))
            ->selectRaw('PerintahKerjaId, SUM(DurasiMenit) as Total')
            ->groupBy('PerintahKerjaId')
            ->pluck('Total', 'PerintahKerjaId');

        $linimasa = $pekerjaan->map(fn (PerintahKerja $satu): array => [
            'Id' => $satu->Id,
            'Jenis' => 'PerintahKerja',
            'Kategori' => $satu->Jenis,
            'Judul' => $satu->Judul,
            'Status' => $satu->Status,
            'Prioritas' => $satu->Prioritas,
            'Pada' => ($satu->DiselesaikanPada ?? $satu->DimulaiPada ?? $satu->DibuatPada)->toIso8601String(),
            'DurasiMenit' => isset($durasi[$satu->Id]) ? (int) $durasi[$satu->Id] : null,
            'Keterangan' => $satu->RingkasanPenyelesaian,
            'Teknisi' => $satu->penugasan->map(fn (PenugasanPerintahKerja $p): ?string => $p->pengguna?->Nama)->filter()->unique()->values()->all(),
        ]);

        $inspeksi = Inspeksi::query()
            ->where('AsetId', $aset->Id)
            ->where('Status', 'Selesai')
            ->orderByDesc('DilaksanakanPada')
            ->limit(10)
            ->get()
            ->map(fn (Inspeksi $satu): array => [
                'Id' => $satu->Id,
                'Jenis' => 'Inspeksi',
                'Kategori' => 'Inspeksi',
                'Judul' => $satu->Nomor,
                'Status' => $satu->Hasil ?? $satu->Status,
                'Prioritas' => null,
                'Pada' => $satu->DilaksanakanPada?->toIso8601String(),
                'DurasiMenit' => null,
                'Keterangan' => $satu->Temuan,
                'Teknisi' => [],
            ]);

        return [
            'ringkasan' => [
                'PekerjaanTahunIni' => $aset->perintahKerja()->where('PerintahKerja.DibuatPada', '>=', $awalTahun)->count(),
                'PersenBeroperasi' => round(max(0, 100 - ($menitHenti / (30 * 24 * 60) * 100)), 1),
                'HariAntarKerusakan' => $jeda === [] ? null : (int) round(array_sum($jeda) / count($jeda)),
            ],
            'linimasa' => array_values($linimasa->concat($inspeksi)
                ->sortByDesc(fn (array $satu): string => (string) $satu['Pada'])
                ->values()
                ->all()),
        ];
    }

    private function iso(mixed $nilai): ?string
    {
        if ($nilai instanceof CarbonImmutable) {
            return $nilai->toIso8601String();
        }

        return is_string($nilai) && $nilai !== '' ? CarbonImmutable::parse($nilai)->toIso8601String() : null;
    }

    /**
     * @param  list<StatusPerintahKerja>  $status
     * @return list<string>
     */
    private function nilai(array $status): array
    {
        return array_map(fn (StatusPerintahKerja $satu): string => $satu->value, $status);
    }
}
