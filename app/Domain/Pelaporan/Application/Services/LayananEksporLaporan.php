<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Ekspor\FormatEkspor;
use App\Shared\Infrastructure\Ekspor\KopOrganisasi;
use App\Shared\Infrastructure\Ekspor\LogoKopEkspor;
use App\Shared\Infrastructure\Ekspor\PenulisEkspor;
use App\Shared\Infrastructure\Ekspor\PenulisEksporPdf;
use Carbon\CarbonImmutable;
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

        /*
         * Dibaca dari penggunanya, bukan dari konteks yang sedang berlaku.
         * Bukan karena konteksnya dapat bocor -- Berkas menolak ditulis bila
         * OrganisasiId-nya tidak cocok dengan konteks, jadi jalur ini memang
         * sudah gagal tertutup -- melainkan supaya method publik ini tidak
         * menuntut pemanggilnya menyetel konteks lebih dulu. Satu-satunya nilai
         * yang benar di sini adalah organisasi pemesan laporannya.
         */
        $organisasi = Organisasi::query()->find($pengguna->OrganisasiId);

        // Hanya PDF yang punya tempat untuk logo; CSV dan XLSX tidak. Cabang
        // yang sama ada di EksporDaftar, dengan alasan yang sama.
        if ($penulis instanceof PenulisEksporPdf) {
            $penulis = $penulis->denganLogo(LogoKopEkspor::dataUri($organisasi?->LogoUrl));
        }

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
                // Laporan KPI justru yang paling mungkin diedarkan ke luar
                // aplikasi, jadi ia harus menyebut rumah sakitnya sendiri --
                // sama seperti seluruh ekspor daftar.
                'Organisasi' => KopOrganisasi::nama($organisasi),
                'Judul' => $judul,
                'Rentang' => $filter->tanggalDari().' s.d. '.$filter->tanggalSampai(),
                'Dibuat' => CarbonImmutable::now($filter->zona)->format('d-m-Y H:i').' '.$filter->zona,
                'Oleh' => $pengguna->Nama,
            ]);

            $isi = file_get_contents($pathSementara);
            if ($isi === false) {
                throw new RuntimeException('Gagal membaca hasil ekspor sementara.');
            }
            Storage::disk($disk)->put($tujuan, $isi);

            $berkas = Berkas::create([
                'OrganisasiId' => $pengguna->OrganisasiId,
                'NamaAsli' => $this->namaBerkas($judul, $format, $filter->zona),
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

    /** Cap waktu nama berkas mengikuti jam dinding organisasi, sama seperti kop "Dibuat". */
    private function namaBerkas(string $judul, FormatEkspor $format, string $zona): string
    {
        $dasar = Str::slug($judul) !== '' ? Str::slug($judul) : 'laporan';

        return $dasar.'-'.CarbonImmutable::now($zona)->format('Ymd-His').'.'.$format->ekstensi();
    }
}
