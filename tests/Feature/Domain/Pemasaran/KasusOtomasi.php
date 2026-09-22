<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananOtomasiPemasaran;
use App\Domain\Pemasaran\Domain\Enums\JenisLangkahOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\OtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiOtomasiPemasaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Support\Str;

/** Dasar test otomasi: penyusun otomasi satu langkah yang siap dipicu. */
abstract class KasusOtomasi extends KasusEmailPemasaran
{
    private ?Organisasi $organisasi = null;

    /** @param list<array{Jenis: string, Konfigurasi: array<string, mixed>}> $langkah */
    protected function buatOtomasi(string $pemicu, array $langkah, bool $aktifkan = true): OtomasiPemasaran
    {
        $layanan = app(LayananOtomasiPemasaran::class);

        $otomasi = $layanan->simpan(null, [
            'Kode' => 'otomasi-'.Str::lower(Str::random(6)),
            'Nama' => 'Otomasi Uji',
            'Pemicu' => $pemicu,
        ]);

        $versi = $this->drafTerakhir($otomasi);

        foreach ($langkah as $urutan => $satu) {
            $layanan->simpanLangkah($versi, null, [
                'Jenis' => $satu['Jenis'],
                'Urutan' => $urutan,
                'Konfigurasi' => $satu['Konfigurasi'],
            ]);
        }

        if ($aktifkan) {
            $layanan->aktifkan($versi->refresh());
            $layanan->ubahAktif($otomasi->refresh(), true);
        }

        return $otomasi->refresh();
    }

    protected function drafTerakhir(OtomasiPemasaran $otomasi): VersiOtomasiPemasaran
    {
        return VersiOtomasiPemasaran::query()
            ->where('OtomasiPemasaranId', $otomasi->Id)
            ->orderByDesc('Nomor')
            ->firstOrFail();
    }

    /** @param array<string, mixed> $konfigurasi */
    protected function langkahAksi(string $aksi, array $konfigurasi = []): array
    {
        return [
            'Jenis' => JenisLangkahOtomasi::Aksi->value,
            'Konfigurasi' => ['Aksi' => $aksi, 'Konfigurasi' => $konfigurasi],
        ];
    }

    /** @param list<array<string, mixed>> $kondisi */
    protected function langkahKondisi(array $kondisi): array
    {
        return [
            'Jenis' => JenisLangkahOtomasi::Kondisi->value,
            'Konfigurasi' => ['Kondisi' => $kondisi],
        ];
    }

    protected function langkahJeda(int $menit): array
    {
        return [
            'Jenis' => JenisLangkahOtomasi::Jeda->value,
            'Konfigurasi' => ['Menit' => $menit],
        ];
    }

    protected function organisasi(): Organisasi
    {
        return $this->organisasi ??= Organisasi::create([
            'Kode' => 'ORG-OTO-'.uniqid(),
            'Nama' => 'Organisasi Otomasi',
            'Status' => 'Aktif',
        ]);
    }
}
