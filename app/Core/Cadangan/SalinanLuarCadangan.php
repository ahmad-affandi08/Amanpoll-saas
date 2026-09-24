<?php

declare(strict_types=1);

namespace App\Core\Cadangan;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Salinan cadangan di luar server (FASE 45).
 *
 * Cadangan yang tinggal di disk yang sama dengan datanya hilang bersama server
 * itu. Kelas ini menyalin berkas cadangan lokal ke disk luar yang dikonfigurasi
 * (`amanpoll.cadangan.disk_luar`, mis. S3 atau Cloudflare R2), memverifikasinya,
 * dan mengambilnya kembali saat pemulihan.
 *
 * Seluruh galat penyimpanan diangkat sebagai AturanBisnisDilanggar, supaya
 * pemanggil cukup menangkap satu jenis dan kegagalan tidak pernah diam.
 */
final class SalinanLuarCadangan
{
    public function __construct(private readonly BerkasCadangan $berkas) {}

    public function aktif(): bool
    {
        return $this->namaDisk() !== null;
    }

    public function namaDisk(): ?string
    {
        $nama = config('amanpoll.cadangan.disk_luar');

        return is_string($nama) && trim($nama) !== '' ? trim($nama) : null;
    }

    /**
     * Cadangan di disk luar, terbaru lebih dulu.
     *
     * @return list<array{nama: string, jenis: string, ukuran: int, dibuat: CarbonImmutable}>
     */
    public function daftar(): array
    {
        $disk = $this->disk();
        $hasil = [];

        try {
            foreach ($disk->files($this->folder()) as $jalur) {
                $nama = basename($jalur);
                $urai = $this->berkas->urai($nama);

                if ($urai === null) {
                    continue;
                }

                $hasil[] = [
                    'nama' => $nama,
                    'jenis' => $urai['jenis'],
                    'ukuran' => $disk->size($jalur),
                    'dibuat' => $urai['dibuat'],
                ];
            }
        } catch (Throwable $galat) {
            throw new AturanBisnisDilanggar('Daftar cadangan di disk luar tidak dapat dibaca: '.$galat->getMessage());
        }

        usort($hasil, fn (array $a, array $b): int => $b['dibuat'] <=> $a['dibuat']);

        return $hasil;
    }

    /**
     * Mengunggah satu berkas cadangan lokal lalu memverifikasinya di tujuan.
     *
     * Berkas dialirkan, tidak dimuat ke memori: arsip berkas bisa berukuran
     * gigabyte sedangkan batas memori PHP shared hosting ratusan megabyte.
     * Salinan yang ukuran atau checksum-nya tidak cocok dihapus dari tujuan
     * supaya tidak ada yang mengira ia utuh; salinan lokalnya tidak disentuh.
     */
    public function kirim(string $jalurLokal): void
    {
        $nama = basename($jalurLokal);
        $tujuan = $this->jalur($nama);
        $disk = $this->disk();

        $ukuran = File::size($jalurLokal);
        $checksum = hash_file('sha256', $jalurLokal);
        $aliran = fopen($jalurLokal, 'rb');

        if ($checksum === false || $aliran === false) {
            throw new AturanBisnisDilanggar("Cadangan lokal {$nama} tidak dapat dibaca untuk diunggah.");
        }

        try {
            $tertulis = $disk->writeStream($tujuan, $aliran);
        } catch (Throwable $galat) {
            throw new AturanBisnisDilanggar("Unggah {$nama} ke disk luar gagal: ".$galat->getMessage());
        } finally {
            if (is_resource($aliran)) {
                fclose($aliran);
            }
        }

        if ($tertulis === false) {
            throw new AturanBisnisDilanggar("Unggah {$nama} ke disk luar gagal.");
        }

        try {
            $ukuranLuar = $disk->size($tujuan);
            $checksumLuar = (bool) config('amanpoll.cadangan.verifikasi_checksum', true)
                ? $this->checksumLuar($disk, $tujuan)
                : $checksum;
        } catch (Throwable $galat) {
            $this->hapusDiam($disk, $tujuan);

            throw new AturanBisnisDilanggar("Salinan luar {$nama} tidak dapat diverifikasi: ".$galat->getMessage());
        }

        if ($ukuranLuar !== $ukuran || ! hash_equals($checksum, $checksumLuar)) {
            $this->hapusDiam($disk, $tujuan);

            throw new AturanBisnisDilanggar(
                "Salinan luar {$nama} tidak cocok dengan aslinya (ukuran {$ukuranLuar} dari {$ukuran} byte); salinan itu dihapus.",
            );
        }

        try {
            // Format sha256sum, supaya pemulihan manual tanpa aplikasi tetap dapat memeriksanya.
            $disk->put($tujuan.'.sha256', $checksum.'  '.$nama."\n");
        } catch (Throwable $galat) {
            throw new AturanBisnisDilanggar("Checksum {$nama} gagal ditulis ke disk luar: ".$galat->getMessage());
        }
    }

    /**
     * Mengunduh satu cadangan dari disk luar ke folder kerja lokal; kembaliannya jalur lokalnya.
     *
     * Unduhan ditulis ke berkas sementara dan baru diberi nama aslinya setelah
     * ukuran dan checksum-nya cocok, supaya unduhan yang terputus tidak pernah
     * tampak seperti cadangan utuh.
     */
    public function ambil(string $nama): string
    {
        if ($this->berkas->urai($nama) === null) {
            throw new AturanBisnisDilanggar("'{$nama}' bukan nama berkas cadangan.");
        }

        $disk = $this->disk();
        $sumber = $this->jalur($nama);
        $folder = $this->berkas->folder();
        File::ensureDirectoryExists($folder);
        $tujuan = $folder.'/'.$nama;
        $sementara = $tujuan.'.unduhan';

        try {
            if (! $disk->exists($sumber)) {
                throw new AturanBisnisDilanggar("Cadangan {$nama} tidak ada di disk luar.");
            }

            $masuk = $disk->readStream($sumber);
            $keluar = fopen($sementara, 'wb');

            if (! is_resource($masuk) || $keluar === false) {
                throw new AturanBisnisDilanggar("Cadangan {$nama} tidak dapat diunduh dari disk luar.");
            }

            stream_copy_to_stream($masuk, $keluar);
            fclose($keluar);
            fclose($masuk);

            $ukuranLuar = $disk->size($sumber);
            $checksumLuar = $disk->exists($sumber.'.sha256')
                ? strtok((string) $disk->get($sumber.'.sha256'), ' ')
                : null;
        } catch (AturanBisnisDilanggar $galat) {
            $this->berkas->hapus($sementara);

            throw $galat;
        } catch (Throwable $galat) {
            $this->berkas->hapus($sementara);

            throw new AturanBisnisDilanggar("Cadangan {$nama} gagal diunduh dari disk luar: ".$galat->getMessage());
        }

        $checksumUnduhan = hash_file('sha256', $sementara);

        if (File::size($sementara) !== $ukuranLuar
            || (is_string($checksumLuar) && ! hash_equals($checksumLuar, (string) $checksumUnduhan))) {
            $this->berkas->hapus($sementara);

            throw new AturanBisnisDilanggar("Unduhan {$nama} tidak cocok dengan salinan luarnya; jangan dipulihkan.");
        }

        File::move($sementara, $tujuan);

        return $tujuan;
    }

    public function hapus(string $nama): void
    {
        $disk = $this->disk();

        try {
            $disk->delete([$this->jalur($nama), $this->jalur($nama).'.sha256']);
        } catch (Throwable $galat) {
            throw new AturanBisnisDilanggar("Cadangan {$nama} gagal dihapus dari disk luar: ".$galat->getMessage());
        }
    }

    private function disk(): Filesystem
    {
        $nama = $this->namaDisk();

        if ($nama === null) {
            throw new AturanBisnisDilanggar('Disk luar cadangan belum diatur (AMANPOLL_CADANGAN_DISK_LUAR).');
        }

        if (config("filesystems.disks.{$nama}") === null) {
            throw new AturanBisnisDilanggar("Disk luar cadangan '{$nama}' tidak ada di config/filesystems.php.");
        }

        if ($nama === config('amanpoll.cadangan.disk')) {
            throw new AturanBisnisDilanggar("Disk luar cadangan '{$nama}' sama dengan disk kerjanya; itu bukan salinan luar.");
        }

        try {
            return Storage::disk($nama);
        } catch (Throwable $galat) {
            // Kredensial kosong membuat adapter S3 gagal dibangun dengan TypeError, bukan galat penyimpanan.
            throw new AturanBisnisDilanggar(
                "Disk luar cadangan '{$nama}' tidak dapat dibuka; periksa AMANPOLL_CADANGAN_LUAR_*: ".$galat->getMessage(),
            );
        }
    }

    private function folder(): string
    {
        return trim((string) config('amanpoll.cadangan.folder_luar', 'cadangan'), '/');
    }

    private function jalur(string $nama): string
    {
        $folder = $this->folder();

        return $folder === '' ? $nama : $folder.'/'.$nama;
    }

    /** Membaca ulang salinan luar sebagai aliran; berkas besar tidak pernah utuh di memori. */
    private function checksumLuar(Filesystem $disk, string $jalur): string
    {
        $aliran = $disk->readStream($jalur);

        if (! is_resource($aliran)) {
            throw new AturanBisnisDilanggar('Salinan luar tidak dapat dibaca kembali.');
        }

        $konteks = hash_init('sha256');
        hash_update_stream($konteks, $aliran);
        fclose($aliran);

        return hash_final($konteks);
    }

    private function hapusDiam(Filesystem $disk, string $jalur): void
    {
        try {
            $disk->delete($jalur);
        } catch (Throwable) {
            // Penghapusan salinan rusak hanya upaya terbaik; galat aslinya yang dilaporkan.
        }
    }
}
