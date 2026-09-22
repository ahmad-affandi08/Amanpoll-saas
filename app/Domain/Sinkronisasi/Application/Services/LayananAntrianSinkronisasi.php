<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Domain\Sinkronisasi\Domain\Enums\KeputusanKonflikSinkronisasi;
use App\Domain\Sinkronisasi\Domain\Enums\StatusAntrianSinkronisasi;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use App\Shared\Domain\Exceptions\AksesDitolak;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use App\Shared\Domain\Exceptions\KonflikData;
use App\Shared\Domain\Exceptions\VersiDataBerubah;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** Antrean mutasi offline (20.03) dan penyelesaian konfliknya (20.06). */
final class LayananAntrianSinkronisasi
{
    /** Batas percobaan otomatis sebelum mutasi ditandai gagal permanen. */
    public const MAKS_PERCOBAAN = 5;

    public function __construct(
        private readonly RegistriOperasiSinkronisasi $registri,
        private readonly LayananPenandaSinkronisasi $penanda,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * Mencatat satu mutasi offline. Pemanggilan ulang dengan KunciOperasi yang
     * sama mengembalikan baris yang sudah ada tanpa mengubah apa pun.
     *
     * Mutasi yang tidak dikenal atau muatannya tidak valid tetap dicatat, tapi
     * langsung berstatus gagal permanen. Ini disengaja: satu mutasi rusak dari
     * klien lama tidak boleh menahan seluruh antrean perangkat.
     *
     * @param  array<string, mixed>  $data
     */
    public function antrikan(PerangkatPengguna $perangkat, array $data): AntrianSinkronisasi
    {
        $operasi = (string) $data['Operasi'];
        $kunciOperasi = (string) $data['KunciOperasi'];

        $adaSebelumnya = $this->cariBerdasarkanKunci($perangkat, $kunciOperasi);
        if ($adaSebelumnya !== null) {
            return $adaSebelumnya;
        }

        $penolakan = $this->periksaMutasi($operasi, $data);
        $penangan = $this->registri->ada($operasi) ? $this->registri->untuk($operasi) : null;

        try {
            return AntrianSinkronisasi::create([
                'OrganisasiId' => $perangkat->OrganisasiId,
                'PerangkatPenggunaId' => $perangkat->Id,
                'KunciOperasi' => $kunciOperasi,
                'JenisEntitas' => $penangan?->jenisEntitas() ?? 'TidakDikenal',
                'EntitasId' => $data['EntitasId'] ?? null,
                'Operasi' => $operasi,
                'VersiKlien' => isset($data['VersiKlien']) ? (int) $data['VersiKlien'] : null,
                'MuatanData' => (array) ($data['MuatanData'] ?? []),
                'Status' => $penolakan === null
                    ? StatusAntrianSinkronisasi::Menunggu->value
                    : StatusAntrianSinkronisasi::Gagal->value,
                'Konflik' => $penolakan,
                'Percobaan' => 0,
                'DiterimaPada' => CarbonImmutable::now(),
                'DiprosesPada' => $penolakan === null ? null : CarbonImmutable::now(),
            ]);
        } catch (QueryException $e) {
            // Dua pengiriman identik yang tiba bersamaan.
            $baris = $this->cariBerdasarkanKunci($perangkat, $kunciOperasi);
            if ($baris === null) {
                throw $e;
            }

            return $baris;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null detail penolakan, null bila mutasi layak diproses
     */
    private function periksaMutasi(string $operasi, array $data): ?array
    {
        if (! $this->registri->ada($operasi)) {
            return [
                'Alasan' => 'OperasiTidakDikenal',
                'Pesan' => "Operasi {$operasi} tidak didukung oleh server ini. Perbarui aplikasi.",
            ];
        }

        $penangan = $this->registri->untuk($operasi);

        if ($penangan->membutuhkanEntitas() && blank($data['EntitasId'] ?? null)) {
            return [
                'Alasan' => 'EntitasTidakDisebut',
                'Pesan' => "Operasi {$operasi} wajib menyertakan EntitasId.",
            ];
        }

        $validator = Validator::make(['MuatanData' => (array) ($data['MuatanData'] ?? [])], $penangan->aturan());
        if ($validator->fails()) {
            return [
                'Alasan' => 'MuatanTidakValid',
                'Pesan' => 'Isi mutasi offline tidak lolos validasi server.',
                'Kesalahan' => $validator->errors()->toArray(),
            ];
        }

        return null;
    }

    /**
     * Memproses seluruh mutasi perangkat yang belum tuntas dan dapat dijalankan
     * otomatis. Baris berstatus Konflik sengaja dilewati karena menunggu
     * keputusan pengguna.
     *
     * @return list<AntrianSinkronisasi>
     */
    public function prosesAntrean(PerangkatPengguna $perangkat, Pengguna $pengguna): array
    {
        $antrean = AntrianSinkronisasi::query()
            ->where('PerangkatPenggunaId', $perangkat->Id)
            ->where('Status', StatusAntrianSinkronisasi::Menunggu->value)
            ->orderBy('DiterimaPada')
            ->orderBy('Id')
            ->get();

        $hasil = [];
        foreach ($antrean as $antrian) {
            $hasil[] = $this->proses($antrian, $pengguna);
        }

        return $hasil;
    }

    /** Menerapkan satu mutasi. */
    public function proses(AntrianSinkronisasi $antrian, Pengguna $pengguna): AntrianSinkronisasi
    {
        if (! $this->klaim($antrian)) {
            return $antrian->refresh();
        }

        $penangan = $this->registri->untuk($antrian->Operasi);
        $antrian->Percobaan++;

        try {
            if (! $penangan->diizinkan($antrian, $pengguna)) {
                return $this->tandaiGagal($antrian, 'AksesDitolak', 'Pengguna tidak berhak menjalankan operasi ini pada entitas tersebut.');
            }

            $konflik = $penangan->periksaKonflik($antrian);
            if ($konflik !== null) {
                return $this->tandaiKonflik($antrian, $konflik);
            }

            $penangan->terapkan($antrian, $pengguna);
        } catch (VersiDataBerubah|KonflikData $e) {
            return $this->tandaiKonflik($antrian, [
                'Alasan' => 'VersiBerbeda',
                'Pesan' => $e->getMessage(),
                'VersiKlien' => $antrian->VersiKlien,
                'VersiServer' => $penangan->versiServer($antrian->EntitasId),
            ]);
        } catch (AturanBisnisDilanggar|AksesDitolak|DataTidakDitemukan $e) {
            // Kegagalan yang tidak akan sembuh dengan percobaan ulang.
            return $this->tandaiGagal($antrian, $e->kodeError(), $e->getMessage());
        } catch (Throwable $e) {
            return $this->tandaiGagalSementara($antrian, $e);
        }

        $antrian->Status = StatusAntrianSinkronisasi::Selesai->value;
        $antrian->Konflik = null;
        $antrian->DiprosesPada = CarbonImmutable::now();
        $antrian->save();

        $this->penanda->catat($antrian->PerangkatPenggunaId, $antrian->JenisEntitas);
        $this->audit->catat('Sinkronisasi.Diterapkan', $antrian->JenisEntitas, $antrian->EntitasId, dataSesudah: [
            'AntrianSinkronisasiId' => $antrian->Id,
            'Operasi' => $antrian->Operasi,
            'KunciOperasi' => $antrian->KunciOperasi,
        ]);

        return $antrian;
    }

    /** Menyelesaikan konflik atas keputusan pengguna (20.06). */
    public function selesaikanKonflik(
        AntrianSinkronisasi $antrian,
        KeputusanKonflikSinkronisasi $keputusan,
        Pengguna $pengguna,
    ): AntrianSinkronisasi {
        if ($antrian->Status !== StatusAntrianSinkronisasi::Konflik->value) {
            throw new AturanBisnisDilanggar('Mutasi ini tidak sedang berstatus konflik.');
        }

        $konflikSebelumnya = $antrian->Konflik;
        $this->audit->catat('Sinkronisasi.KonflikDiselesaikan', $antrian->JenisEntitas, $antrian->EntitasId, dataSesudah: [
            'AntrianSinkronisasiId' => $antrian->Id,
            'Operasi' => $antrian->Operasi,
            'Keputusan' => $keputusan->value,
            'Konflik' => $konflikSebelumnya,
        ]);

        if ($keputusan === KeputusanKonflikSinkronisasi::PakaiServer) {
            $antrian->Status = StatusAntrianSinkronisasi::Dibatalkan->value;
            $antrian->DiprosesPada = CarbonImmutable::now();
            $antrian->save();

            return $antrian;
        }

        // Terapkan ulang di atas versi server terbaru.
        $versiServer = $this->registri->untuk($antrian->Operasi)->versiServer($antrian->EntitasId);
        $antrian->VersiKlien = $versiServer === null ? null : max(0, $versiServer);
        $antrian->Status = StatusAntrianSinkronisasi::Menunggu->value;
        $antrian->Percobaan = 0;
        $antrian->Konflik = null;
        $antrian->save();

        return $this->proses($antrian, $pengguna);
    }

    /**
     * Mengembalikan mutasi yang tertinggal di status Diproses ke antrean.
     *
     * Proses yang mati mendadak (timeout, worker dimatikan) meninggalkan baris
     * terklaim yang tidak akan pernah diambil lagi, dan pekerjaan lapangan
     * teknisi diam-diam tidak pernah sampai. Ambang waktunya sengaja jauh lebih
     * panjang dari durasi wajar satu mutasi supaya proses yang masih berjalan
     * tidak direbut.
     *
     * @return int jumlah mutasi yang dikembalikan ke antrean
     */
    public function pulihkanTerhenti(int $menit = 15): int
    {
        return AntrianSinkronisasi::query()
            ->withoutGlobalScopes()
            ->where('Status', StatusAntrianSinkronisasi::Diproses->value)
            ->where('Percobaan', '<', self::MAKS_PERCOBAAN)
            ->whereNotNull('DiprosesPada')
            ->where('DiprosesPada', '<', CarbonImmutable::now()->subMinutes($menit))
            ->update(['Status' => StatusAntrianSinkronisasi::Menunggu->value]);
    }

    /** Klaim atomik supaya dua worker (atau worker. */
    private function klaim(AntrianSinkronisasi $antrian): bool
    {
        $sekarang = CarbonImmutable::now();

        $terklaim = AntrianSinkronisasi::query()
            ->whereKey($antrian->Id)
            ->where('Status', StatusAntrianSinkronisasi::Menunggu->value)
            ->update([
                'Status' => StatusAntrianSinkronisasi::Diproses->value,
                'DiprosesPada' => $sekarang,
            ]);

        if ($terklaim === 0) {
            return false;
        }

        $antrian->Status = StatusAntrianSinkronisasi::Diproses->value;
        $antrian->DiprosesPada = $sekarang;

        return true;
    }

    private function cariBerdasarkanKunci(PerangkatPengguna $perangkat, string $kunciOperasi): ?AntrianSinkronisasi
    {
        return AntrianSinkronisasi::query()
            ->where('PerangkatPenggunaId', $perangkat->Id)
            ->where('KunciOperasi', $kunciOperasi)
            ->first();
    }

    /** @param array<string, mixed> $konflik */
    private function tandaiKonflik(AntrianSinkronisasi $antrian, array $konflik): AntrianSinkronisasi
    {
        $antrian->Status = StatusAntrianSinkronisasi::Konflik->value;
        $antrian->Konflik = $konflik;
        $antrian->DiprosesPada = CarbonImmutable::now();
        $antrian->save();

        $this->audit->catat('Sinkronisasi.Konflik', $antrian->JenisEntitas, $antrian->EntitasId, dataSesudah: [
            'AntrianSinkronisasiId' => $antrian->Id,
            'Operasi' => $antrian->Operasi,
            'Konflik' => $konflik,
        ]);

        return $antrian;
    }

    private function tandaiGagal(AntrianSinkronisasi $antrian, string $alasan, string $pesan): AntrianSinkronisasi
    {
        $antrian->Status = StatusAntrianSinkronisasi::Gagal->value;
        $antrian->Konflik = ['Alasan' => $alasan, 'Pesan' => $pesan];
        $antrian->DiprosesPada = CarbonImmutable::now();
        $antrian->save();

        return $antrian;
    }

    /** Kegagalan tak terduga (mis. */
    private function tandaiGagalSementara(AntrianSinkronisasi $antrian, Throwable $e): AntrianSinkronisasi
    {
        report($e);

        if ($antrian->Percobaan >= self::MAKS_PERCOBAAN) {
            return $this->tandaiGagal($antrian, 'BatasPercobaan', 'Mutasi gagal diterapkan setelah '.self::MAKS_PERCOBAAN.' percobaan.');
        }

        $antrian->Status = StatusAntrianSinkronisasi::Menunggu->value;
        $antrian->Konflik = [
            'Alasan' => 'GagalSementara',
            'Pesan' => 'Mutasi gagal diterapkan dan akan dicoba lagi.',
            'Percobaan' => $antrian->Percobaan,
        ];
        $antrian->save();

        return $antrian;
    }
}
