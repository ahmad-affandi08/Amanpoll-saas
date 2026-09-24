<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\UploadedFile;

/**
 * Menyimpan tanda tangan pengguna ke profilnya (PRD 8.22).
 *
 * Dipakai dari halaman profil dan dari konfirmasi penerima ("gambar sekali lalu
 * tersimpan"). Gambar melewati mesin kompresi seperti berkas lain. Tanda tangan
 * lama tidak dihapus: konfirmasi yang sudah tercatat tetap merujuknya.
 */
final class SimpanTandaTanganPengguna
{
    public function __construct(
        private readonly PenyimpanBerkas $penyimpanBerkas,
        private readonly LayananAudit $audit,
    ) {}

    public function jalankan(Pengguna $pengguna, UploadedFile $gambar): Berkas
    {
        $sebelum = $pengguna->TandaTanganBerkasId;

        $berkas = $this->penyimpanBerkas->simpanUnggahan($gambar, $pengguna->Id, ['Keperluan' => 'TandaTanganProfil']);

        $pengguna->forceFill(['TandaTanganBerkasId' => $berkas->Id])->save();

        $this->audit->catat(
            'Pengguna.TandaTanganDisimpan',
            'Pengguna',
            $pengguna->Id,
            ['TandaTanganBerkasId' => $sebelum],
            ['TandaTanganBerkasId' => $berkas->Id],
        );

        return $berkas;
    }
}
