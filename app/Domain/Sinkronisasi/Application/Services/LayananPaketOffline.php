<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\Services;

use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Application\Services\AturanTandaTanganPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JawabanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/** Paket data yang dibawa teknisi ke lapangan (20.05). */
final class LayananPaketOffline
{
    /** Status pekerjaan yang masih relevan dibawa offline. */
    private const STATUS_AKTIF = [
        StatusPerintahKerja::Ditugaskan,
        StatusPerintahKerja::Diterima,
        StatusPerintahKerja::Dikerjakan,
        StatusPerintahKerja::MenungguSukuCadang,
        StatusPerintahKerja::MenungguPenyedia,
        StatusPerintahKerja::Dijeda,
        StatusPerintahKerja::MenungguVerifikasi,
    ];

    /** Batas jumlah pekerjaan yang diturunkan ke perangkat dalam satu paket. */
    private const BATAS_PENUGASAN = 50;

    public function __construct(
        private readonly RegistriOperasiSinkronisasi $registri,
        private readonly AturanTandaTanganPenerima $tandaTanganPenerima,
    ) {}

    /**
     * @return array{
     *     Penugasan: array<int, array<string, mixed>>,
     *     Aset: array<int, array<string, mixed>>,
     *     DaftarPeriksa: array<int, array<string, mixed>>,
     *     Token: array<string, string>,
     *     OperasiDidukung: list<string>,
     *     Pengaturan: array{TandaTanganPenerimaWajib: bool},
     *     DibuatPada: string
     * }
     */
    public function bangun(Pengguna $pengguna): array
    {
        $perintahKerja = $this->perintahKerjaTeknisi($pengguna);
        $namaLokasi = $this->namaLokasi($perintahKerja);
        $penugasan = array_map(
            fn (PerintahKerja $item): array => $this->ringkasPerintahKerja($item, $namaLokasi, $pengguna),
            $perintahKerja->all(),
        );
        $aset = $this->ringkasAset($perintahKerja);
        $daftarPeriksa = $this->ringkasDaftarPeriksa($perintahKerja);

        return [
            'Penugasan' => $penugasan,
            'Aset' => $aset,
            'DaftarPeriksa' => $daftarPeriksa,
            'Token' => [
                'PerintahKerja' => $this->sidikJari($penugasan),
                'Aset' => $this->sidikJari($aset),
                'PelaksanaanDaftarPeriksa' => $this->sidikJari($daftarPeriksa),
            ],
            'OperasiDidukung' => $this->registri->daftarOperasi(),
            // Setelan organisasi yang dibaca layar tanpa sinyal (Ringkasan: tanda tangan wajib atau opsional).
            'Pengaturan' => [
                'TandaTanganPenerimaWajib' => $this->tandaTanganPenerima->wajib($pengguna->OrganisasiId),
            ],
            'DibuatPada' => now()->toIso8601String(),
        ];
    }

    /** @return Collection<int, PerintahKerja> */
    private function perintahKerjaTeknisi(Pengguna $pengguna): Collection
    {
        $perintahKerjaId = PenugasanPerintahKerja::query()
            ->where('PenggunaId', $pengguna->Id)
            ->whereIn('Status', ['Ditugaskan', 'Diterima'])
            ->pluck('PerintahKerjaId');

        return PerintahKerja::query()
            ->with('asetPekerjaan')
            ->whereIn('Id', $perintahKerjaId)
            ->whereIn('Status', array_map(fn (StatusPerintahKerja $status): string => $status->value, self::STATUS_AKTIF))
            ->orderByRaw('COALESCE(BatasPenyelesaianPada, DijadwalkanSelesaiPada) IS NULL')
            ->orderByRaw('COALESCE(BatasPenyelesaianPada, DijadwalkanSelesaiPada)')
            ->limit(self::BATAS_PENUGASAN)
            ->get();
    }

    /**
     * Nama lokasi diambil lewat satu query terpisah, bukan relasi per baris,
     * supaya paket tetap satu query per jenis data.
     *
     * @param  Collection<int, PerintahKerja>  $perintahKerja
     * @return array<string, string>
     */
    private function namaLokasi(Collection $perintahKerja): array
    {
        $lokasiId = $perintahKerja->pluck('LokasiId')->filter()->unique();
        if ($lokasiId->isEmpty()) {
            return [];
        }

        /** @var array<string, string> $nama */
        $nama = Lokasi::query()->whereIn('Id', $lokasiId)->pluck('Nama', 'Id')->all();

        return $nama;
    }

    /**
     * @param  array<string, string>  $namaLokasi
     * @return array<string, mixed>
     */
    private function ringkasPerintahKerja(PerintahKerja $perintahKerja, array $namaLokasi, Pengguna $pengguna): array
    {
        return [
            'Id' => $perintahKerja->Id,
            'Nomor' => $perintahKerja->Nomor,
            'Judul' => $perintahKerja->Judul,
            'Deskripsi' => $perintahKerja->Deskripsi,
            'Jenis' => $perintahKerja->Jenis,
            'Status' => $perintahKerja->Status,
            'Prioritas' => $perintahKerja->Prioritas,
            'Versi' => $perintahKerja->Versi,
            'NamaLokasi' => $perintahKerja->LokasiId === null ? null : ($namaLokasi[$perintahKerja->LokasiId] ?? null),
            'DijadwalkanMulaiPada' => $perintahKerja->DijadwalkanMulaiPada?->toIso8601String(),
            'BatasPenyelesaianPada' => $perintahKerja->BatasPenyelesaianPada?->toIso8601String(),
            'AsetId' => $perintahKerja->asetPekerjaan->pluck('AsetId')->all(),
            'PerluResponsPenugasan' => $this->perluResponsPenugasan($perintahKerja, $pengguna),
            'StatusTujuan' => $this->statusTujuanYangBolehDipilih($perintahKerja, $pengguna),
        ];
    }

    /**
     * Hanya transisi yang benar-benar diizinkan policy yang ditawarkan ke
     * perangkat. Tanpa penyaringan ini, teknisi akan mengantrikan perubahan
     * yang pasti ditolak server dan baru mengetahuinya setelah kembali online.
     *
     * @return array<int, string>
     */
    private function statusTujuanYangBolehDipilih(PerintahKerja $perintahKerja, Pengguna $pengguna): array
    {
        $tujuan = StatusPerintahKerja::from($perintahKerja->Status)->tujuanYangDiizinkan();

        return array_values(array_filter(
            array_map(fn (StatusPerintahKerja $status): string => $status->value, $tujuan),
            fn (string $status): bool => Gate::forUser($pengguna)->allows('ubahStatus', [$perintahKerja, $status]),
        ));
    }

    /** Penugasan yang belum direspons tetap dapat diterima atau ditolak dari lapangan. */
    private function perluResponsPenugasan(PerintahKerja $perintahKerja, Pengguna $pengguna): bool
    {
        return PenugasanPerintahKerja::query()
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->where('PenggunaId', $pengguna->Id)
            ->where('Status', StatusPenugasanPerintahKerja::Ditugaskan->value)
            ->exists();
    }

    /**
     * @param  Collection<int, PerintahKerja>  $perintahKerja
     * @return array<int, array<string, mixed>>
     */
    private function ringkasAset(Collection $perintahKerja): array
    {
        $asetId = $perintahKerja->flatMap(fn (PerintahKerja $item): array => $item->asetPekerjaan->pluck('AsetId')->all())
            ->filter()
            ->unique()
            ->values();

        if ($asetId->isEmpty()) {
            return [];
        }

        return Aset::query()
            ->with(['lokasi:Id,Nama'])
            ->whereIn('Id', $asetId)
            ->orderBy('KodeAset')
            ->get()
            ->map(fn (Aset $aset): array => [
                'Id' => $aset->Id,
                'KodeAset' => $aset->KodeAset,
                // Kode label fisik, supaya pindai QR tetap menemukan asetnya tanpa sinyal.
                'KodeQr' => $aset->KodeQr,
                'KodeBatang' => $aset->KodeBatang,
                'Nama' => $aset->Nama,
                'NomorSeri' => $aset->NomorSeri,
                'Status' => $aset->Status,
                'Kondisi' => $aset->Kondisi,
                'TingkatKritis' => $aset->TingkatKritis,
                'NamaLokasi' => $aset->lokasi?->Nama,
                // Tanpa sinyal gambarnya tampil bila masih ada di cache HTTP peramban; bila tidak, ikon 3D kategori.
                'FotoUtamaThumbnailUrl' => GaleriFotoAset::urlThumbnail($aset->FotoUtamaBerkasId),
                'Versi' => $aset->Versi,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, PerintahKerja>  $perintahKerja
     * @return array<int, array<string, mixed>>
     */
    private function ringkasDaftarPeriksa(Collection $perintahKerja): array
    {
        if ($perintahKerja->isEmpty()) {
            return [];
        }

        $pelaksanaan = PelaksanaanDaftarPeriksa::query()
            ->whereIn('PerintahKerjaId', $perintahKerja->pluck('Id'))
            ->where('Status', '!=', 'Selesai')
            ->get();

        if ($pelaksanaan->isEmpty()) {
            return [];
        }

        $butir = ButirTemplatDaftarPeriksa::query()
            ->whereIn('TemplatDaftarPeriksaId', $pelaksanaan->pluck('TemplatDaftarPeriksaId')->unique())
            ->orderBy('Urutan')
            ->get()
            ->groupBy('TemplatDaftarPeriksaId');

        $templat = TemplatDaftarPeriksa::query()
            ->whereIn('Id', $pelaksanaan->pluck('TemplatDaftarPeriksaId')->unique())
            ->get()
            ->keyBy('Id');

        $jawaban = JawabanDaftarPeriksa::query()
            ->whereIn('PelaksanaanDaftarPeriksaId', $pelaksanaan->pluck('Id'))
            ->get()
            ->groupBy('PelaksanaanDaftarPeriksaId');

        return $pelaksanaan
            ->map(fn (PelaksanaanDaftarPeriksa $item): array => [
                'Id' => $item->Id,
                'PerintahKerjaId' => $item->PerintahKerjaId,
                'AsetId' => $item->AsetId,
                'Status' => $item->Status,
                'NamaTemplat' => $templat->get($item->TemplatDaftarPeriksaId)?->Nama,
                'VersiTemplat' => $templat->get($item->TemplatDaftarPeriksaId)?->VersiTemplat,
                'Catatan' => $item->Catatan,
                'Butir' => ($butir[$item->TemplatDaftarPeriksaId] ?? collect())
                    ->map(fn (ButirTemplatDaftarPeriksa $b): array => [
                        'Id' => $b->Id,
                        'Urutan' => $b->Urutan,
                        'Pertanyaan' => $b->Pertanyaan,
                        'TipeJawaban' => $b->TipeJawaban,
                        'Satuan' => $b->Satuan,
                        'Wajib' => $b->Wajib,
                        'Pilihan' => $b->Pilihan,
                        'NilaiMinimum' => $b->NilaiMinimum === null ? null : (float) $b->NilaiMinimum,
                        'NilaiMaksimum' => $b->NilaiMaksimum === null ? null : (float) $b->NilaiMaksimum,
                    ])->values()->all(),
                'Jawaban' => ($jawaban[$item->Id] ?? collect())
                    ->map(fn (JawabanDaftarPeriksa $j): array => [
                        'ButirTemplatDaftarPeriksaId' => $j->ButirTemplatDaftarPeriksaId,
                        'NilaiTeks' => $j->NilaiTeks,
                        'NilaiAngka' => $j->NilaiAngka === null ? null : (float) $j->NilaiAngka,
                        'NilaiBoolean' => $j->NilaiBoolean,
                        'NilaiTanggal' => $j->NilaiTanggal?->toIso8601String(),
                        'Catatan' => $j->Catatan,
                    ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /** @param array<int, array<string, mixed>> $bagian */
    private function sidikJari(array $bagian): string
    {
        return hash('sha256', (string) json_encode($bagian));
    }
}
