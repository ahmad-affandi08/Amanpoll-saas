<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Database\Eloquent\Builder;

final class AsetPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Lihat');
    }

    public function view(Pengguna $pengguna, Aset $aset): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Lihat');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Buat');
    }

    public function update(Pengguna $pengguna, Aset $aset): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Ubah');
    }

    public function delete(Pengguna $pengguna, Aset $aset): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Hapus');
    }

    /**
     * Menambah foto ke galeri aset (PRD 8.4 "Foto Aset"): pemegang `Aset.Ubah`,
     * atau teknisi yang sedang ditugaskan (penugasan Ditugaskan/Diterima) pada
     * perintah kerja yang masih dikerjakan untuk aset ini, sebagai aset utama
     * maupun salah satu aset tiketnya. Teknisi hanya menambah, tidak menghapus
     * atau mengganti foto utama (`kelolaFoto`).
     */
    public function tambahFoto(Pengguna $pengguna, Aset $aset): bool
    {
        return $this->update($pengguna, $aset) || $this->ditugaskanPadaPekerjaanAktif($pengguna, $aset);
    }

    /** Menghapus foto dan memilih foto utama: hanya pemegang `Aset.Ubah`. */
    public function kelolaFoto(Pengguna $pengguna, Aset $aset): bool
    {
        return $this->update($pengguna, $aset);
    }

    /**
     * Melihat satu lampiran aset tanpa hak mengelolanya: foto galeri terbuka bagi
     * siapa pun yang boleh melihat aset itu (termasuk pelapor Mode Lapangan dalam
     * lingkupnya) atau boleh menambah fotonya. Lampiran kategori lain tetap
     * tertutup. Dipanggil `RegistriEntitas::bolehLihatLampiran` untuk unduhan
     * dan thumbnail berkas.
     */
    public function lihatLampiran(Pengguna $pengguna, Aset $aset, ?string $kategori): bool
    {
        if ($kategori !== GaleriFotoAset::KATEGORI) {
            return false;
        }

        return $this->view($pengguna, $aset) || $this->ditugaskanPadaPekerjaanAktif($pengguna, $aset);
    }

    private function ditugaskanPadaPekerjaanAktif(Pengguna $pengguna, Aset $aset): bool
    {
        return PerintahKerja::query()
            ->whereIn('Status', array_map(fn (StatusPerintahKerja $status): string => $status->value, StatusPerintahKerja::dikerjakanTeknisi()))
            ->whereHas('asetPekerjaan', fn (Builder $kueri) => $kueri->where('AsetId', $aset->Id))
            ->whereHas('penugasan', fn (Builder $kueri) => $kueri
                ->where('PenggunaId', $pengguna->Id)
                ->whereIn('Status', [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value]))
            ->exists();
    }
}
