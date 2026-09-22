<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Pelaporan\Domain\Contracts\PenulisEkspor;
use App\Domain\Pelaporan\Domain\Enums\FormatEkspor;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** Pembuatan berkas ekspor laporan (21.05). */
final class LayananEksporLaporan
{
    /** Penanda pada Berkas.DataTambahan yang membedakan ekspor dari lampiran biasa. */
    public const JENIS_BERKAS = 'EksporLaporan';

    /** @var array<string, PenulisEkspor> */
    private array $penulis = [];

    public function __construct(
        private readonly PenyusunBarisLaporan $penyusun,
        private readonly LayananNotifikasi $notifikasi,
        private readonly LayananAudit $audit,
    ) {}

    public function daftarkanPenulis(PenulisEkspor $penulis): void
    {
        $this->penulis[$penulis->format()->value] = $penulis;
    }

    /** @param  list<string>  $kunciKpi */
    public function jalankan(
        Pengguna $pengguna,
        array $kunciKpi,
        FilterMetrik $filter,
        FormatEkspor $format,
        string $judul,
    ): Berkas {
        $penulis = $this->penulis[$format->value]
            ?? throw new RuntimeException("Tidak ada penulis untuk format {$format->value}.");

        $baris = $this->penyusun->baris($kunciKpi, $filter, $pengguna);

        $disk = (string) config('amanpoll.disk_berkas', 'local');
        $namaPenyimpanan = (string) Str::ulid().'.'.$format->ekstensi();
        $tujuan = 'ekspor-laporan/'.$pengguna->OrganisasiId.'/'.$namaPenyimpanan;

        // Ditulis ke berkas sementara lebih dulu.
        $pathSementara = tempnam(sys_get_temp_dir(), 'ekspor-');
        if ($pathSementara === false) {
            throw new RuntimeException('Tidak dapat membuat berkas sementara untuk ekspor.');
        }

        try {
            $penulis->tulis($pathSementara, PenyusunBarisLaporan::KEPALA, $baris, [
                'Judul' => $judul,
                'Rentang' => $filter->dari->toDateString().' s.d. '.$filter->sampai->toDateString(),
                'Dibuat' => now()->toDateTimeString(),
                'Oleh' => $pengguna->Nama,
            ]);

            $isi = file_get_contents($pathSementara);
            if ($isi === false) {
                throw new RuntimeException('Gagal membaca hasil ekspor sementara.');
            }
            Storage::disk($disk)->put($tujuan, $isi);

            $berkas = Berkas::create([
                'OrganisasiId' => $pengguna->OrganisasiId,
                'NamaAsli' => $this->namaBerkas($judul, $format),
                'NamaPenyimpanan' => $namaPenyimpanan,
                'MediaPenyimpanan' => $disk,
                'LokasiPenyimpanan' => $tujuan,
                'JenisMime' => $format->jenisMime(),
                'UkuranByte' => strlen($isi),
                'HashSha256' => hash('sha256', $isi),
                'DataTambahan' => [
                    'Jenis' => self::JENIS_BERKAS,
                    'Format' => $format->value,
                    'Judul' => $judul,
                    'KunciKpi' => $kunciKpi,
                    'Filter' => $filter->keArray(),
                    'JumlahBaris' => count($baris),
                ],
                'DiunggahOleh' => $pengguna->Id,
            ]);
        } finally {
            @unlink($pathSementara);
        }

        $this->notifikasi->kirim(
            penggunaId: $pengguna->Id,
            jenisPeristiwa: 'Laporan.EksporSelesai',
            isi: "Ekspor \"{$judul}\" ({$format->label()}) sudah siap diunduh.",
            judul: 'Ekspor laporan selesai',
            jenisEntitas: 'Berkas',
            entitasId: $berkas->Id,
        );

        $this->audit->catat('Laporan.Diekspor', 'Berkas', $berkas->Id, dataSesudah: [
            'Format' => $format->value,
            'KunciKpi' => $kunciKpi,
            'JumlahBaris' => count($baris),
        ]);

        return $berkas;
    }

    private function namaBerkas(string $judul, FormatEkspor $format): string
    {
        $dasar = Str::slug($judul) !== '' ? Str::slug($judul) : 'laporan';

        return $dasar.'-'.now()->format('Ymd-His').'.'.$format->ekstensi();
    }
}
