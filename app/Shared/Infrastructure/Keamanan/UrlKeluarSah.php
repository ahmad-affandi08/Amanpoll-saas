<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Keamanan;

/**
 * URL keluar yang lolos `PenjagaUrlKeluar::periksa()`.
 *
 * `url` adalah bentuk baku yang disusun ulang dari komponen yang diperiksa,
 * bukan teks masukan, sehingga host yang dilihat klien HTTP pasti host yang
 * diperiksa. `alamatTersemat` adalah IP yang dipaksakan ke sambungan.
 */
final readonly class UrlKeluarSah
{
    public function __construct(
        public string $url,
        public string $skema,
        public string $host,
        public int $port,
        public string $alamatTersemat,
        public bool $hostBerupaIp,
    ) {}

    /**
     * Entri `CURLOPT_RESOLVE` ("host:port:ip") yang menyematkan alamat hasil
     * pemeriksaan ke sambungan, sehingga resolusi ulang saat menyambung
     * (DNS rebinding) tidak dapat membelokkan tujuan. Host berupa IP literal
     * tidak di-resolve, jadi tidak butuh entri.
     */
    public function entriResolve(): ?string
    {
        if ($this->hostBerupaIp) {
            return null;
        }

        $alamat = str_contains($this->alamatTersemat, ':') ? "[{$this->alamatTersemat}]" : $this->alamatTersemat;

        return "{$this->host}:{$this->port}:{$alamat}";
    }
}
