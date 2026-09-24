<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Core\Izin\PemeriksaLingkupBaris;
use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Application\Services\AturanKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class PerintahKerjaPolicy
{
    /** Kategori lampiran foto hasil kerja teknisi yang boleh dilihat pelapor keluhannya. */
    public const KATEGORI_FOTO_SESUDAH = 'FotoSesudah';

    public function __construct(
        private readonly PemeriksaIzin $izin,
        private readonly PemeriksaLingkupBaris $pemeriksaLingkup,
        private readonly KonteksOrganisasi $konteks,
        private readonly AturanKonfirmasiPenerima $aturanKonfirmasi,
    ) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function view(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $this->dapatMengelola($pengguna) || $this->ditugaskan($pengguna, $perintahKerja);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->dapatMengelola($pengguna);
    }

    public function assign(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $this->dapatMengelola($pengguna);
    }

    /** Memindahkan perintah kerja ke unit pengelola lain (PRD 8.21): hak koordinator, bukan teknisi. */
    public function alihkanUnitPengelola(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $this->dapatMengelola($pengguna);
    }

    public function ubahStatus(Pengguna $pengguna, PerintahKerja $perintahKerja, string $statusTujuan): bool
    {
        if ($this->dapatMengelola($pengguna)) {
            return true;
        }

        return $this->ditugaskan($pengguna, $perintahKerja)
            && in_array($statusTujuan, [
                StatusPerintahKerja::Dikerjakan->value,
                StatusPerintahKerja::MenungguSukuCadang->value,
                StatusPerintahKerja::MenungguPenyedia->value,
                StatusPerintahKerja::Dijeda->value,
                StatusPerintahKerja::MenungguVerifikasi->value,
            ], true);
    }

    public function responsPenugasan(Pengguna $pengguna, PerintahKerja $perintahKerja, PenugasanPerintahKerja $penugasan): bool
    {
        return $penugasan->PerintahKerjaId === $perintahKerja->Id && $penugasan->PenggunaId === $pengguna->Id;
    }

    public function operate(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $this->dapatMengelola($pengguna) || $this->ditugaskan($pengguna, $perintahKerja);
    }

    /**
     * Melihat satu lampiran perintah kerja tanpa hak mengelolanya (PRD 8.20):
     * pelapor keluhan asal perintah kerja ini boleh melihat foto Sesudah, dan
     * hanya itu. Lampiran kategori lain tetap tertutup baginya. Dipanggil
     * `RegistriEntitas::bolehLihatLampiran` untuk unduhan berkas.
     */
    public function lihatLampiran(Pengguna $pengguna, PerintahKerja $perintahKerja, ?string $kategori): bool
    {
        if ($kategori !== self::KATEGORI_FOTO_SESUDAH || $perintahKerja->KeluhanId === null) {
            return false;
        }

        return Keluhan::query()
            ->whereKey($perintahKerja->KeluhanId)
            ->where('PelaporId', $pengguna->Id)
            ->exists();
    }

    /**
     * Cara 1 konfirmasi penerima (PRD 8.22): pelapor keluhan asal, dari akunnya sendiri.
     * Keadaan tiketnya (sedang menunggu konfirmasi atau belum) diperiksa Action.
     */
    public function konfirmasiSebagaiPelapor(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        if ($pengguna->Status !== 'Aktif' || $perintahKerja->KeluhanId === null) {
            return false;
        }

        $pelaporKeluhan = Keluhan::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->whereKey($perintahKerja->KeluhanId)
            ->value('PelaporId');

        return $pelaporKeluhan === $pengguna->Id && ! $this->aturanKonfirmasi->ditugaskan($perintahKerja, $pengguna->Id);
    }

    /**
     * Cara 2 konfirmasi penerima (PRD 8.22): siapa pun yang memindai QR-nya, asalkan
     * pengguna aktif di organisasi yang sama, lingkupnya mencakup tiket ini, dan bukan
     * teknisi yang mengerjakannya. Tidak butuh izin tambahan: penerima pekerjaan
     * biasanya bukan pemegang izin pemeliharaan.
     */
    public function konfirmasiLewatPindai(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $pengguna->Status === 'Aktif'
            && $pengguna->OrganisasiId === $perintahKerja->OrganisasiId
            && $perintahKerja->OrganisasiId === $this->konteks->id()
            && $this->pemeriksaLingkup->mencakup($pengguna->Id, $perintahKerja)
            && ! $this->aturanKonfirmasi->ditugaskan($perintahKerja, $pengguna->Id);
    }

    public function manageCost(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $this->dapatMengelola($pengguna);
    }

    private function ditugaskan(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $perintahKerja->penugasan()
            ->where('PenggunaId', $pengguna->Id)
            ->whereIn('Status', ['Ditugaskan', 'Diterima'])
            ->exists();
    }

    private function dapatMengelola(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'PerintahKerja.Kelola');
    }
}
