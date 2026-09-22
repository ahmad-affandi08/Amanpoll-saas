<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Tindakan;

use App\Domain\Pemasaran\Application\Services\PenempelTagProspek;
use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;

/** Menempelkan tag pada prospek; menempel dua kali tidak menghasilkan baris kedua. */
final class TindakanTambahTag implements TindakanOtomasi
{
    public function __construct(private readonly PenempelTagProspek $penempel) {}

    public function kode(): string
    {
        return 'TambahTag';
    }

    public function label(): string
    {
        return 'Tambah tag';
    }

    /** @return array<string, mixed> */
    public function aturan(): array
    {
        return [
            'Tag' => ['required', 'array', 'min:1'],
            'Tag.*' => ['required', 'string', 'max:80'],
        ];
    }

    /** @param array<string, mixed> $konfigurasi */
    public function jalankan(KonteksOtomasi $konteks, array $konfigurasi): string
    {
        $prospek = $konteks->wajibProspek();
        /** @var list<string> $tag */
        $tag = array_values((array) ($konfigurasi['Tag'] ?? []));

        $this->penempel->tempel($prospek, $tag);

        return 'Tag ditempel: '.implode(', ', $tag).'.';
    }
}
