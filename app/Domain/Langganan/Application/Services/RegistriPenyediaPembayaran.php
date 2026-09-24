<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Services;

use App\Domain\Langganan\Domain\Contracts\PenyediaPembayaran;
use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use LogicException;

/**
 * Daftar penyedia pembayaran yang terpasang (22.06) dan mana yang dinyalakan di
 * konsol platform (PRD 8.23). Terpasang berarti kodenya dikenal; aktif berarti
 * admin platform sudah mengisi kredensial dan menyalakannya.
 */
final class RegistriPenyediaPembayaran
{
    /** @var array<string, PenyediaPembayaran> */
    private array $penyedia = [];

    public function __construct(private readonly PembacaKredensialPenyedia $pembaca) {}

    public function daftarkan(PenyediaPembayaran $penyedia): void
    {
        $kode = $penyedia->kode();

        if (isset($this->penyedia[$kode])) {
            throw new LogicException("Penyedia pembayaran {$kode} sudah terdaftar.");
        }

        $this->penyedia[$kode] = $penyedia;
    }

    public function ada(string $kode): bool
    {
        return isset($this->penyedia[$kode]);
    }

    public function untuk(string $kode): PenyediaPembayaran
    {
        return $this->penyedia[$kode]
            ?? throw new DataTidakDitemukan("Penyedia pembayaran {$kode} tidak terdaftar.");
    }

    /** @return array<string, PenyediaPembayaran> */
    public function semua(): array
    {
        return $this->penyedia;
    }

    /** Penyedia utama di konsol platform; sebelum konsol diatur, pilihan konfigurasi. */
    public function bawaan(): PenyediaPembayaran
    {
        $kodeUtama = $this->pembaca->kodeUtama(KategoriPenyediaLayanan::Pembayaran);

        if ($kodeUtama !== null && $this->ada($kodeUtama)) {
            return $this->penyedia[$kodeUtama];
        }

        return $this->untuk((string) config('amanpoll.langganan.penyedia_pembayaran', 'TransferManual'));
    }

    /**
     * Penyedia yang boleh dipilih tenant, utama lebih dulu. Bila belum satu pun
     * dinyalakan, penyedia bawaan (transfer manual) menjadi cadangan supaya tagihan
     * tetap dapat dibayar.
     *
     * @return array<string, PenyediaPembayaran>
     */
    public function aktif(): array
    {
        $aktif = [];

        foreach ($this->pembaca->kodeAktif(KategoriPenyediaLayanan::Pembayaran) as $kode) {
            if ($this->ada($kode)) {
                $aktif[$kode] = $this->penyedia[$kode];
            }
        }

        if ($aktif === []) {
            $bawaan = $this->bawaan();
            $aktif[$bawaan->kode()] = $bawaan;
        }

        return $aktif;
    }

    /** Penyedia pilihan tenant; tanpa pilihan, yang pertama di daftar aktif. */
    public function aktifUntuk(?string $kode): PenyediaPembayaran
    {
        $aktif = $this->aktif();

        if ($kode === null || $kode === '') {
            return $aktif[array_key_first($aktif)];
        }

        return $aktif[$kode]
            ?? throw new AturanBisnisDilanggar('Metode pembayaran itu tidak tersedia.');
    }
}
