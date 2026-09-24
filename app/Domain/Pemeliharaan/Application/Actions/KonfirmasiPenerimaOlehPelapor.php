<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Izin\ScopeLingkup;
use App\Domain\Pemeliharaan\Application\Services\AturanKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Application\Services\PencatatKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\HasilKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\MetodeKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\UploadedFile;

/**
 * Cara 1 (PRD 8.22): pelapor keluhan asal mengonfirmasi dari akunnya sendiri begitu
 * teknisi menyerahkan pekerjaan. "Sudah beres" boleh dengan penilaian dan membuat
 * keluhannya tertutup otomatis setelah koordinator memverifikasi; "Masih bermasalah"
 * mengembalikan perintah kerja ke Dikerjakan.
 */
final class KonfirmasiPenerimaOlehPelapor
{
    public function __construct(
        private readonly PencatatKonfirmasiPenerima $pencatat,
        private readonly AturanKonfirmasiPenerima $aturan,
    ) {}

    public function jalankan(
        PerintahKerja $perintahKerja,
        Pengguna $pelapor,
        HasilKonfirmasiPenerima $hasil,
        ?int $penilaian,
        ?string $komentar,
        ?UploadedFile $gambar,
    ): KonfirmasiPenerimaPerintahKerja {
        $pelaporKeluhan = $perintahKerja->KeluhanId === null ? null : Keluhan::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->whereKey($perintahKerja->KeluhanId)
            ->value('PelaporId');

        if ($pelaporKeluhan !== $pelapor->Id) {
            throw new AturanBisnisDilanggar('Hanya pelapor keluhan asal yang dapat mengonfirmasi dari layar ini.');
        }
        if ($this->aturan->ditugaskan($perintahKerja, $pelapor->Id)) {
            throw new AturanBisnisDilanggar('Teknisi yang mengerjakan tidak dapat mengonfirmasi pekerjaannya sendiri.');
        }

        return $this->pencatat->catat(
            $perintahKerja,
            MetodeKonfirmasiPenerima::Pelapor,
            $hasil,
            [StatusPerintahKerja::MenungguVerifikasi],
            $hasil === HasilKonfirmasiPenerima::Diterima
                ? ['Penilaian' => $penilaian, 'Ulasan' => $komentar]
                : ['Alasan' => $komentar],
            $pelapor,
            $pelapor->Id,
            $hasil === HasilKonfirmasiPenerima::Diterima ? $gambar : null,
        );
    }
}
