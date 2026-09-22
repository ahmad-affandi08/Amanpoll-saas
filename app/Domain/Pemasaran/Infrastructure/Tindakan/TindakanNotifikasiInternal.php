<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Tindakan;

use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Domain\Pemasaran\Domain\Enums\JenisAktivitasProspek;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AktivitasProspek;
use Carbon\CarbonImmutable;

/** Menaruh catatan untuk tim growth di timeline prospeknya, tempat mereka memang membacanya. */
final class TindakanNotifikasiInternal implements TindakanOtomasi
{
    public function kode(): string
    {
        return 'NotifikasiInternal';
    }

    public function label(): string
    {
        return 'Notifikasi internal';
    }

    /** @return array<string, mixed> */
    public function aturan(): array
    {
        return [
            'Judul' => ['required', 'string', 'max:190'],
            'Isi' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @param array<string, mixed> $konfigurasi */
    public function jalankan(KonteksOtomasi $konteks, array $konfigurasi): string
    {
        $prospek = $konteks->wajibProspek();
        $judul = (string) ($konfigurasi['Judul'] ?? '');

        AktivitasProspek::create([
            'ProspekId' => $prospek->Id,
            'Jenis' => JenisAktivitasProspek::Otomasi->value,
            'Judul' => $judul,
            'Isi' => $konfigurasi['Isi'] ?? null,
            'TerjadiPada' => CarbonImmutable::now(),
        ]);

        return "Notifikasi internal dicatat: {$judul}.";
    }
}
