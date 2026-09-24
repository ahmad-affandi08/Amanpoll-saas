<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Domain\Pemeliharaan\Application\Services\PencatatKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\HasilKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\MetodeKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use Illuminate\Http\UploadedFile;

/**
 * Cara 3 (PRD 8.22): penerima tanpa akun (tamu, penyewa, pihak luar) menandatangani di
 * HP teknisi, berikut namanya. Bisa dicatat selama pekerjaan masih di tangan teknisi
 * atau sudah menunggu verifikasi, karena layar Ringkasan mengambil tanda tangannya
 * sebelum laporan dikirim dan antrean offline mengunggahnya lebih dulu dari mutasi
 * "selesai". Kunci perangkat membuat kiriman ulang dari antrean tidak menggandakan.
 *
 * Hanya "Diterima": penerima yang tidak puas cukup tidak menandatangani, dan teknisi
 * melanjutkan pekerjaannya.
 */
final class KonfirmasiPenerimaDiPerangkat
{
    /** @return list<StatusPerintahKerja> */
    public static function statusDiizinkan(): array
    {
        return [
            StatusPerintahKerja::Diterima,
            StatusPerintahKerja::Dikerjakan,
            StatusPerintahKerja::MenungguSukuCadang,
            StatusPerintahKerja::MenungguPenyedia,
            StatusPerintahKerja::Dijeda,
            StatusPerintahKerja::MenungguVerifikasi,
        ];
    }

    public function __construct(private readonly PencatatKonfirmasiPenerima $pencatat) {}

    public function jalankan(
        PerintahKerja $perintahKerja,
        string $teknisiId,
        string $namaPenerima,
        ?string $jabatanPenerima,
        UploadedFile $gambar,
        ?string $kunciPerangkat,
    ): KonfirmasiPenerimaPerintahKerja {
        return $this->pencatat->catat(
            $perintahKerja,
            MetodeKonfirmasiPenerima::TandaTanganPerangkat,
            HasilKonfirmasiPenerima::Diterima,
            self::statusDiizinkan(),
            [
                'NamaPenerima' => $namaPenerima,
                'JabatanPenerima' => $jabatanPenerima,
                'KunciPerangkat' => $kunciPerangkat,
            ],
            null,
            $teknisiId,
            $gambar,
        );
    }
}
