<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Izin\PemeriksaLingkupBaris;
use App\Domain\Pemeliharaan\Application\Services\AturanKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Application\Services\PencatatKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\HasilKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\MetodeKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\UploadedFile;

/**
 * Cara 2 (PRD 8.22): penerima memindai QR bertoken dari HP teknisi lalu mengonfirmasi
 * dari akunnya. Token diperiksa di controller; di sini penerimanya: pengguna aktif di
 * organisasi perintah kerja, lingkupnya mencakup perintah kerja itu, dan bukan teknisi
 * yang mengerjakannya.
 */
final class KonfirmasiPenerimaLewatPindai
{
    public function __construct(
        private readonly PencatatKonfirmasiPenerima $pencatat,
        private readonly AturanKonfirmasiPenerima $aturan,
        private readonly PemeriksaLingkupBaris $pemeriksaLingkup,
    ) {}

    public function jalankan(
        PerintahKerja $perintahKerja,
        Pengguna $penerima,
        HasilKonfirmasiPenerima $hasil,
        ?string $alasan,
        ?UploadedFile $gambar,
    ): KonfirmasiPenerimaPerintahKerja {
        if ($penerima->Status !== 'Aktif' || $penerima->OrganisasiId !== $perintahKerja->OrganisasiId) {
            throw new AturanBisnisDilanggar('Akunmu tidak dapat mengonfirmasi pekerjaan ini.');
        }
        if (! $this->pemeriksaLingkup->mencakup($penerima->Id, $perintahKerja)) {
            throw new AturanBisnisDilanggar('Pekerjaan ini di luar lingkup akunmu.');
        }
        if ($this->aturan->ditugaskan($perintahKerja, $penerima->Id)) {
            throw new AturanBisnisDilanggar('Teknisi yang mengerjakan tidak dapat mengonfirmasi pekerjaannya sendiri.');
        }

        return $this->pencatat->catat(
            $perintahKerja,
            MetodeKonfirmasiPenerima::PindaiQr,
            $hasil,
            [StatusPerintahKerja::MenungguVerifikasi],
            ['Alasan' => $alasan],
            $penerima,
            $penerima->Id,
            $hasil === HasilKonfirmasiPenerima::Diterima ? $gambar : null,
        );
    }
}
