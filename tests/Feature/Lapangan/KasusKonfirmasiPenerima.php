<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Application\Services\AturanKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Application\Actions\SimpanKonfigurasiOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Data uji konfirmasi penerima (PRD 8.22): keluhan pelapor yang dikerjakan teknisi,
 * koordinator pemegang `PerintahKerja.Kelola`, dan penerima lain di lingkup yang sama.
 */
abstract class KasusKonfirmasiPenerima extends KasusPelapor
{
    protected const PERANGKAT = ['IdentitasPerangkat' => 'hp-konfirmasi-uji', 'NamaPerangkat' => 'Ponsel', 'Platform' => 'Android'];

    protected const RINGKASAN = "Tindakan: Ganti kontaktor K1\nKondisi aset: Berfungsi normal";

    protected Lokasi $ruang;

    protected KategoriKeluhan $kategoriListrik;

    protected Pengguna $pelapor;

    protected Pengguna $teknisi;

    protected Pengguna $koordinator;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->ruang = $this->lokasi('Ruang Server');
        $this->kategoriListrik = $this->kategori('Listrik');
        $this->pelapor = $this->pelaporDi($this->ruang);
        $this->teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $this->koordinator = $this->penggunaMeja(['PerintahKerja.Kelola', 'Keluhan.Kelola']);
    }

    /** Keluhan pelapor yang Diproses, dengan perintah kerja yang ditugaskan ke teknisi. */
    protected function pekerjaanDariKeluhan(StatusPerintahKerja $status = StatusPerintahKerja::MenungguVerifikasi): PerintahKerja
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategoriListrik, $this->ruang, transisi: [
            StatusKeluhan::Ditinjau, StatusKeluhan::Diterima, StatusKeluhan::Diproses,
        ]);

        return $this->tugaskan($keluhan, $this->teknisi, $status->value);
    }

    protected function gambarTandaTangan(string $nama = 'ttd.png'): UploadedFile
    {
        return UploadedFile::fake()->image($nama, 320, 120);
    }

    protected function wajibkanKonfirmasi(): void
    {
        $this->dalamOrganisasi(fn () => app(SimpanKonfigurasiOrganisasi::class)
            ->jalankan($this->organisasi->Id, AturanKonfirmasiPenerima::KUNCI_KONFIGURASI, true));
    }

    /** @return TestResponse<Response> */
    protected function verifikasi(PerintahKerja $pekerjaan): TestResponse
    {
        return $this->actingAs($this->koordinator)->putJson("/pemeliharaan/perintah-kerja/{$pekerjaan->Id}/status", [
            'Status' => StatusPerintahKerja::Selesai->value,
            'RingkasanPenyelesaian' => self::RINGKASAN,
            'Versi' => $this->versi($pekerjaan),
        ]);
    }

    /** Teknisi menyelesaikan pekerjaan lewat antrean offline, seperti layar Ringkasan. */
    protected function selesaikanLewatAntrean(PerintahKerja $pekerjaan): TestResponse
    {
        return $this->actingAs($this->teknisi)->postJson('/offline/antrian', [...self::PERANGKAT, 'Mutasi' => [[
            'KunciOperasi' => 'op-selesai-'.uniqid(),
            'Operasi' => 'PerintahKerja.UbahStatus',
            'EntitasId' => $pekerjaan->Id,
            'VersiKlien' => $this->versi($pekerjaan),
            'MuatanData' => ['Status' => 'MenungguVerifikasi', 'Ringkasan' => self::RINGKASAN],
        ]]])->assertOk();
    }

    protected function statusPekerjaan(PerintahKerja $pekerjaan): string
    {
        return (string) $this->dalamOrganisasi(fn () => PerintahKerja::query()->withoutGlobalScopes()->whereKey($pekerjaan->Id)->value('Status'));
    }

    protected function versi(PerintahKerja $pekerjaan): int
    {
        return (int) $this->dalamOrganisasi(fn () => PerintahKerja::query()->withoutGlobalScopes()->whereKey($pekerjaan->Id)->value('Versi'));
    }

    protected function keluhanDari(PerintahKerja $pekerjaan): Keluhan
    {
        return $this->dalamOrganisasi(fn (): Keluhan => Keluhan::query()->withoutGlobalScopes()->findOrFail($pekerjaan->KeluhanId));
    }

    /** @return list<KonfirmasiPenerimaPerintahKerja> */
    protected function konfirmasi(PerintahKerja $pekerjaan): array
    {
        return array_values($this->dalamOrganisasi(fn () => KonfirmasiPenerimaPerintahKerja::query()
            ->withoutGlobalScopes()
            ->where('PerintahKerjaId', $pekerjaan->Id)
            ->orderBy('DibuatPada')
            ->get()
            ->all()));
    }

    protected function tandaTanganProfil(Pengguna $pengguna): ?string
    {
        $nilai = Pengguna::query()->withoutGlobalScopes()->whereKey($pengguna->Id)->value('TandaTanganBerkasId');

        return is_string($nilai) ? $nilai : null;
    }
}
