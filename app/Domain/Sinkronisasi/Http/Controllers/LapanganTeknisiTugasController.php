<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Pemeliharaan\Application\Services\AturanTandaTanganPenerima;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AnalisisKegagalan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KodeKegagalan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JawabanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarTeknisi;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tiket kerja teknisi di Mode Lapangan (DESIGN §36.6 layar 05–12).
 *
 * Controller ini hanya membaca. Setiap perubahan dikirim layar ke endpoint
 * domain yang sama dengan dasbor -- antrean offline FASE 20 untuk status,
 * waktu kerja, catatan, dan checklist; `pemeliharaan.perintah-kerja.*` untuk
 * analisis kegagalan dan permintaan suku cadang; Kolaborasi untuk foto --
 * sehingga policy dan aturan bisnisnya tidak pernah disalin ke sini.
 */
final class LapanganTeknisiTugasController extends Controller
{
    /** Kategori lampiran yang dipakai layar foto dan tanda tangan. */
    public const KATEGORI_FOTO = ['FotoSebelum', 'FotoSesudah', AturanTandaTanganPenerima::KATEGORI_LAMPIRAN];

    public function __construct(
        private readonly PenyusunLayarTeknisi $penyusun,
        private readonly AturanTandaTanganPenerima $tandaTanganPenerima,
    ) {}

    /** Tiket Saya (layar 05): tab Hari ini/Terlambat dihitung klien dari tiket aktif. */
    public function index(Request $request): Response
    {
        $pengguna = $request->user('web');

        return Inertia::render('Lapangan/Teknisi/Tugas', [
            'tiket' => $this->penyusun->tiketAktif($pengguna),
            'selesai' => $this->penyusun->tiketSelesai($pengguna),
        ]);
    }

    /** Detail tiket (layar 06). Tiket teknisi lain ditolak policy yang sama dengan dasbor. */
    public function show(Request $request, PerintahKerja $perintahKerja): Response
    {
        $this->authorize('view', $perintahKerja);
        $pengguna = $request->user('web');
        $tiket = $this->muat($perintahKerja, $pengguna);
        // Ringkasan tiket memuat keluhan dengan kolom terbatas; layar detail butuh uraian dan pelapornya.
        $tiket->load(['keluhan.pelapor:Id,Nama', 'keluhan.lokasi:Id,Nama']);
        $aset = $this->penyusun->asetUtama($tiket);
        $keluhan = $tiket->keluhan;

        return Inertia::render('Lapangan/Teknisi/DetailTiket', [
            'tiket' => [
                ...$this->penyusun->ringkas($tiket, $pengguna),
                'Deskripsi' => $tiket->Deskripsi,
                'StatusTujuan' => $this->penyusun->statusTujuan($tiket, $pengguna),
            ],
            'keluhan' => $keluhan === null ? null : [
                'Nomor' => $keluhan->Nomor,
                'Pelapor' => $keluhan->pelapor->Nama ?? $keluhan->NamaPelaporEksternal,
                'Lokasi' => $keluhan->lokasi?->Nama,
                'Deskripsi' => $keluhan->Deskripsi,
            ],
            'daftarPeriksa' => $this->ringkasDaftarPeriksa($tiket),
            'riwayatAset' => $aset === null ? null : [
                'JumlahPekerjaan' => $aset->perintahKerja()->count(),
                'TerakhirDiservisPada' => $this->tanggalIso($aset->perintahKerja()->whereNotNull('DiselesaikanPada')->max('DiselesaikanPada')),
            ],
        ]);
    }

    /**
     * Mengerjakan tiket (layar 07–12) dalam satu halaman: langkah berpindah di
     * klien, jadi seluruh alurnya tetap terbuka tanpa sinyal setelah halaman
     * ini sekali tersimpan di perangkat.
     */
    public function kerjakan(Request $request, PerintahKerja $perintahKerja): Response
    {
        $this->authorize('operate', $perintahKerja);
        $pengguna = $request->user('web');
        $tiket = $this->muat($perintahKerja, $pengguna);
        $tiket->loadMissing(['penugasan.ditugaskanOleh:Id,Nama,Jabatan', 'dibuatOleh:Id,Nama,Jabatan']);
        $penugasan = $tiket->penugasan->first(fn (PenugasanPerintahKerja $satu): bool => $satu->PenggunaId === $pengguna->Id);
        $pengawas = $penugasan->ditugaskanOleh ?? $tiket->dibuatOleh;
        $berikutnya = collect($this->penyusun->tiketAktif($pengguna))->first(fn (array $satu): bool => $satu['Id'] !== $tiket->Id);

        return Inertia::render('Lapangan/Teknisi/Kerjakan', [
            'tiket' => [
                ...$this->penyusun->ringkas($tiket, $pengguna),
                'Deskripsi' => $tiket->Deskripsi,
                'RingkasanPenyelesaian' => $tiket->RingkasanPenyelesaian,
                'StatusTujuan' => $this->penyusun->statusTujuan($tiket, $pengguna),
            ],
            'daftarPeriksa' => $this->daftarPeriksaLengkap($tiket),
            'kodeKegagalan' => array_values(KodeKegagalan::query()
                ->where('Aktif', true)
                ->where('Jenis', 'Masalah')
                ->orderBy('Nama')
                ->get(['Id', 'Kode', 'Nama'])
                ->map(fn (KodeKegagalan $kode): array => ['Id' => $kode->Id, 'Kode' => $kode->Kode, 'Nama' => $kode->Nama])
                ->all()),
            'analisis' => $this->analisis($tiket),
            'permintaanSukuCadang' => $this->permintaanSukuCadang($tiket),
            'foto' => $this->foto($tiket),
            'waktuKerja' => $this->waktuKerja($tiket, $pengguna),
            'pengawas' => $pengawas instanceof Pengguna ? ['Nama' => $pengawas->Nama, 'Jabatan' => $pengawas->Jabatan] : null,
            // Layar Ringkasan menandai tanda tangan "Wajib"; server tetap menolak penyelesaian tanpa lampirannya.
            'tandaTanganWajib' => $this->tandaTanganPenerima->wajib($tiket->OrganisasiId),
            'berikutnya' => $berikutnya,
        ]);
    }

    private function muat(PerintahKerja $perintahKerja, Pengguna $pengguna): PerintahKerja
    {
        return $this->penyusun->muatRelasi(PerintahKerja::query()->whereKey($perintahKerja->Id), $pengguna)->firstOrFail();
    }

    /** Pelaksanaan checklist yang sedang berjalan; bila semua sudah final, yang terakhir. */
    private function pelaksanaan(PerintahKerja $perintahKerja): ?PelaksanaanDaftarPeriksa
    {
        return PelaksanaanDaftarPeriksa::query()
            ->with('templatDaftarPeriksa:Id,Nama,VersiTemplat')
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->orderByRaw("CASE WHEN Status = 'Selesai' THEN 1 ELSE 0 END")
            ->orderByDesc('MulaiPada')
            ->orderBy('Id')
            ->first();
    }

    /** @return array<string, mixed>|null */
    private function ringkasDaftarPeriksa(PerintahKerja $perintahKerja): ?array
    {
        $pelaksanaan = $this->pelaksanaan($perintahKerja);

        if ($pelaksanaan === null) {
            return null;
        }

        return [
            'Id' => $pelaksanaan->Id,
            'NamaTemplat' => $pelaksanaan->templatDaftarPeriksa?->Nama,
            'Status' => $pelaksanaan->Status,
            'JumlahButir' => ButirTemplatDaftarPeriksa::query()->where('TemplatDaftarPeriksaId', $pelaksanaan->TemplatDaftarPeriksaId)->count(),
        ];
    }

    /**
     * Bentuknya sama dengan `DaftarPeriksaOffline` di paket FASE 20, ditambah skor.
     *
     * @return array<string, mixed>|null
     */
    private function daftarPeriksaLengkap(PerintahKerja $perintahKerja): ?array
    {
        $pelaksanaan = $this->pelaksanaan($perintahKerja);

        if ($pelaksanaan === null) {
            return null;
        }

        return [
            'Id' => $pelaksanaan->Id,
            'PerintahKerjaId' => $pelaksanaan->PerintahKerjaId,
            'AsetId' => $pelaksanaan->AsetId,
            'Status' => $pelaksanaan->Status,
            'Skor' => $pelaksanaan->Skor === null ? null : (float) $pelaksanaan->Skor,
            'NamaTemplat' => $pelaksanaan->templatDaftarPeriksa?->Nama,
            'VersiTemplat' => $pelaksanaan->templatDaftarPeriksa?->VersiTemplat,
            'Catatan' => $pelaksanaan->Catatan,
            'Butir' => array_values(ButirTemplatDaftarPeriksa::query()
                ->where('TemplatDaftarPeriksaId', $pelaksanaan->TemplatDaftarPeriksaId)
                ->orderBy('Urutan')
                ->get()
                ->map(fn (ButirTemplatDaftarPeriksa $butir): array => [
                    'Id' => $butir->Id,
                    'Urutan' => $butir->Urutan,
                    'Pertanyaan' => $butir->Pertanyaan,
                    'TipeJawaban' => $butir->TipeJawaban,
                    'Satuan' => $butir->Satuan,
                    'Wajib' => $butir->Wajib,
                    'Pilihan' => $butir->Pilihan,
                    'NilaiMinimum' => $butir->NilaiMinimum === null ? null : (float) $butir->NilaiMinimum,
                    'NilaiMaksimum' => $butir->NilaiMaksimum === null ? null : (float) $butir->NilaiMaksimum,
                ])
                ->all()),
            'Jawaban' => array_values(JawabanDaftarPeriksa::query()
                ->where('PelaksanaanDaftarPeriksaId', $pelaksanaan->Id)
                ->whereNotNull('DijawabPada')
                ->get()
                ->map(fn (JawabanDaftarPeriksa $jawaban): array => [
                    'ButirTemplatDaftarPeriksaId' => $jawaban->ButirTemplatDaftarPeriksaId,
                    'NilaiTeks' => $jawaban->NilaiTeks,
                    'NilaiAngka' => $jawaban->NilaiAngka === null ? null : (float) $jawaban->NilaiAngka,
                    'NilaiBoolean' => $jawaban->NilaiBoolean,
                    'Catatan' => $jawaban->Catatan,
                    'Sesuai' => $jawaban->Sesuai,
                ])
                ->all()),
        ];
    }

    /** @return array<string, mixed>|null */
    private function analisis(PerintahKerja $perintahKerja): ?array
    {
        $analisis = AnalisisKegagalan::query()->where('PerintahKerjaId', $perintahKerja->Id)->first();

        return $analisis === null ? null : [
            'KodeMasalahId' => $analisis->KodeMasalahId,
            'AkarMasalah' => $analisis->AkarMasalah,
            'TindakanKorektif' => $analisis->TindakanKorektif,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function permintaanSukuCadang(PerintahKerja $perintahKerja): array
    {
        return array_values(ReservasiSukuCadang::query()
            ->with(['sukuCadang:Id,Kode,Nama,SatuanDasar', 'gudang:Id,Nama'])
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->orderByDesc('DibuatPada')
            ->orderBy('Id')
            ->get()
            ->map(fn (ReservasiSukuCadang $reservasi): array => [
                'Id' => $reservasi->Id,
                'Jumlah' => (float) $reservasi->Jumlah,
                'Status' => $reservasi->Status,
                'DibuatPada' => $reservasi->DibuatPada->toIso8601String(),
                'NamaSukuCadang' => $reservasi->sukuCadang?->Nama,
                'KodeSukuCadang' => $reservasi->sukuCadang?->Kode,
                'Satuan' => $reservasi->sukuCadang?->SatuanDasar,
                'NamaGudang' => $reservasi->gudang?->Nama,
            ])
            ->all());
    }

    /** @return list<array<string, mixed>> */
    private function foto(PerintahKerja $perintahKerja): array
    {
        return array_values(LampiranEntitas::query()
            ->with('berkas:Id,NamaAsli,JenisMime')
            ->where('JenisEntitas', 'PerintahKerja')
            ->where('EntitasId', $perintahKerja->Id)
            ->whereIn('Kategori', self::KATEGORI_FOTO)
            ->orderBy('DibuatPada')
            ->orderBy('Id')
            ->get()
            ->map(fn (LampiranEntitas $lampiran): array => [
                'Id' => $lampiran->Id,
                'Kategori' => $lampiran->Kategori,
                'Keterangan' => $lampiran->Keterangan,
                'DibuatPada' => $lampiran->DibuatPada->toIso8601String(),
                'Url' => $lampiran->berkas === null ? null : route('kolaborasi.berkas.unduh', $lampiran->berkas->Id, false),
            ])
            ->all());
    }

    /** @return array{TotalMenit: int, MulaiPertama: string|null} */
    private function waktuKerja(PerintahKerja $perintahKerja, Pengguna $pengguna): array
    {
        $kueri = WaktuKerja::query()->where('PerintahKerjaId', $perintahKerja->Id)->where('PenggunaId', $pengguna->Id);
        $mulai = (clone $kueri)->min('MulaiPada');

        return [
            'TotalMenit' => (int) (clone $kueri)->sum('DurasiMenit'),
            'MulaiPertama' => is_string($mulai) ? CarbonImmutable::parse($mulai)->toIso8601String() : null,
        ];
    }

    private function tanggalIso(mixed $nilai): ?string
    {
        return is_string($nilai) ? CarbonImmutable::parse($nilai)->toIso8601String() : null;
    }
}
