<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Langganan\Application\Services\RegistriPenyediaPembayaran;
use App\Domain\Langganan\Domain\Contracts\MenerimaKartuDiMuka;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Menegakkan kebijakan "kartu diperlukan" saat mendaftar trial
 * (MARKETING.md 12).
 *
 * Gagal tertutup dua kali. Kebijakan yang menyala sementara penyedianya tidak
 * dapat menyimpan kartu berarti pendaftaran mandiri ditolak seluruhnya —
 * membiarkannya lewat sama saja dengan mematikan kebijakan tanpa ada yang
 * memutuskannya.
 */
final class PenjagaKartuTrial
{
    public function __construct(
        private readonly PembacaKonfigurasiTrial $konfigurasi,
        private readonly RegistriPenyediaPembayaran $registri,
    ) {}

    public function pastikanBoleh(?string $tokenKartu): void
    {
        if (! $this->konfigurasi->berlaku()->kartuDiperlukan) {
            return;
        }

        $penyedia = $this->registri->bawaan();

        if (! $penyedia instanceof MenerimaKartuDiMuka) {
            throw new AturanBisnisDilanggar(
                'Pendaftaran trial menuntut kartu, tetapi penyedia pembayaran yang aktif '
                .'tidak dapat menerima kartu di muka. Hubungi tim penjualan.',
            );
        }

        if ($tokenKartu === null || trim($tokenKartu) === '') {
            throw new AturanBisnisDilanggar('Pendaftaran trial ini memerlukan metode pembayaran.');
        }

        if (! $penyedia->metodePembayaranSah($tokenKartu)) {
            throw new AturanBisnisDilanggar('Metode pembayaran tidak dapat diverifikasi.');
        }
    }

    /** Apakah formulir pendaftaran perlu menampilkan bidang kartu. */
    public function kartuDiminta(): bool
    {
        return $this->konfigurasi->berlaku()->kartuDiperlukan;
    }

    public function penyediaSiapMenerimaKartu(): bool
    {
        return $this->registri->bawaan() instanceof MenerimaKartuDiMuka;
    }
}
