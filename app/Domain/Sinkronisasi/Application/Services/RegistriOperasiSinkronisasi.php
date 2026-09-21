<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\Services;

use App\Domain\Sinkronisasi\Domain\Contracts\PenanganOperasiSinkronisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Daftar putih operasi yang boleh dikirim dari antrean offline (20.03).
 * Operasi di luar daftar ini ditolak, sehingga klien tidak dapat memakai
 * antrean sebagai jalur pintas ke use-case sembarang.
 */
final class RegistriOperasiSinkronisasi
{
    /** @var array<string, PenanganOperasiSinkronisasi> */
    private array $penangan = [];

    public function daftarkan(PenanganOperasiSinkronisasi $penangan): void
    {
        $this->penangan[$penangan->operasi()] = $penangan;
    }

    public function ada(string $operasi): bool
    {
        return isset($this->penangan[$operasi]);
    }

    public function untuk(string $operasi): PenanganOperasiSinkronisasi
    {
        return $this->penangan[$operasi]
            ?? throw new AturanBisnisDilanggar("Operasi sinkronisasi {$operasi} tidak dikenal.");
    }

    /** @return list<string> */
    public function daftarOperasi(): array
    {
        return array_keys($this->penangan);
    }

    /** @return list<string> */
    public function daftarJenisEntitas(): array
    {
        $jenis = [];
        foreach ($this->penangan as $penangan) {
            $jenis[$penangan->jenisEntitas()] = true;
        }

        return array_keys($jenis);
    }
}
