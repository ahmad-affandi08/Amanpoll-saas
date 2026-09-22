<?php

declare(strict_types=1);

namespace Tests\Dukungan;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaEmailPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanEmail;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanPenyedia;
use RuntimeException;

/** Penyedia email untuk test: mencatat apa yang dikirim, tanpa benar-benar mengirim. */
final class PenyediaEmailPalsu implements PenyediaEmailPemasaran
{
    /** @var list<PesanEmail> */
    public array $terkirim = [];

    /** @var array<string, StatusKirimanPenyedia> */
    public array $laporanStatus = [];

    public bool $gagalkan = false;

    public function kode(): string
    {
        return 'Palsu';
    }

    public function kirim(PesanEmail $pesan): string
    {
        if ($this->gagalkan) {
            throw new RuntimeException('Penyedia sedang tidak dapat dihubungi.');
        }

        $this->terkirim[] = $pesan;

        return 'msg-'.count($this->terkirim);
    }

    /**
     * @param  list<string>  $idPesan
     * @return list<StatusKirimanPenyedia>
     */
    public function statusKiriman(array $idPesan): array
    {
        return array_values(array_filter(array_map(
            fn (string $id): ?StatusKirimanPenyedia => $this->laporanStatus[$id] ?? null,
            $idPesan,
        )));
    }

    public function laporkan(string $idPesan, StatusPengirimanEmail $status): void
    {
        $this->laporanStatus[$idPesan] = new StatusKirimanPenyedia($idPesan, $status);
    }

    /** @return list<string> */
    public function alamatTerkirim(): array
    {
        return array_map(fn (PesanEmail $pesan): string => $pesan->kepada, $this->terkirim);
    }
}
