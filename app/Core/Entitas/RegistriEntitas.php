<?php

declare(strict_types=1);

namespace App\Core\Entitas;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\AksesDitolak;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** Peta JenisEntitas (string polimorfik dipakai Berkas/Tag/KolomKustom/ Komentar) ke model Eloquent sungguhan. */
final class RegistriEntitas
{
    /**
     * @var array<string, array{kelas: class-string<Model>, izinKelola: string}>
     */
    private array $peta = [];

    public function __construct(private readonly PemeriksaIzin $pemeriksaIzin) {}

    /**
     * @param  class-string<Model>  $kelasModel
     */
    public function daftarkan(string $jenisEntitas, string $kelasModel, string $izinKelola): void
    {
        $this->peta[$jenisEntitas] = ['kelas' => $kelasModel, 'izinKelola' => $izinKelola];
    }

    public function dikenal(string $jenisEntitas): bool
    {
        return array_key_exists($jenisEntitas, $this->peta);
    }

    /**
     * @return list<string>
     */
    public function jenisDikenal(): array
    {
        return array_keys($this->peta);
    }

    public function izinKelolaUntuk(string $jenisEntitas): string
    {
        return $this->peta[$jenisEntitas]['izinKelola']
            ?? throw new DataTidakDitemukan("Jenis entitas '{$jenisEntitas}' tidak dikenal.");
    }

    /** Dipakai seragam oleh semua controller yang menempel data (lampiran. */
    public function pastikanBolehKelola(Pengguna $pengguna, string $jenisEntitas): void
    {
        if (! $this->bolehKelola($pengguna, $jenisEntitas)) {
            throw new AksesDitolak("Anda tidak memiliki izin untuk mengelola {$jenisEntitas}.");
        }
    }

    public function bolehKelola(Pengguna $pengguna, string $jenisEntitas): bool
    {
        return $this->pemeriksaIzin->boleh($pengguna->Id, $this->izinKelolaUntuk($jenisEntitas));
    }

    /** Mengembalikan baris entitas HANYA jika ada. */
    public function cariEntitas(string $jenisEntitas, string $entitasId): Model
    {
        $kelas = $this->peta[$jenisEntitas]['kelas']
            ?? throw new DataTidakDitemukan("Jenis entitas '{$jenisEntitas}' tidak dikenal.");

        $entitas = $kelas::query()->find($entitasId);
        if (! $entitas) {
            throw new DataTidakDitemukan("Entitas {$jenisEntitas}#{$entitasId} tidak ditemukan.");
        }

        return $entitas;
    }

    /**
     * Versi batch dari cariEntitas() untuk menghindari N+1 saat memproses
     * banyak baris berjenis entitas sama sekaligus (mis. inbox persetujuan).
     *
     * @param  list<string>  $entitasId
     * @return Collection<string, Model> dikunci berdasarkan Id
     */
    public function cariBanyakEntitas(string $jenisEntitas, array $entitasId): Collection
    {
        $kelas = $this->peta[$jenisEntitas]['kelas']
            ?? throw new DataTidakDitemukan("Jenis entitas '{$jenisEntitas}' tidak dikenal.");

        return $kelas::query()->whereIn('Id', $entitasId)->get()->keyBy('Id');
    }
}
