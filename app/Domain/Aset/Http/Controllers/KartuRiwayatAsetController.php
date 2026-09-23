<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Services\PenyusunKartuRiwayatAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Cetak Kartu Riwayat Alat satu aset.
 *
 * Berkas akreditasi SNARS/JCI meminta riwayat satu alat di atas satu lembar
 * bertanda tangan. Sampai sekarang riwayatnya hanya dapat dilihat per tab di
 * layar atau diekspor per tabel, sehingga menyiapkan satu alat untuk surveior
 * berarti menyalin ulang dari beberapa menu.
 */
final class KartuRiwayatAsetController extends Controller
{
    public function cetak(Aset $aset, PenyusunKartuRiwayatAset $penyusun): Response
    {
        $this->authorize('view', $aset);

        // PDF-nya selesai dibangun sebelum respons dikembalikan, bukan di dalam
        // closure streamDownload: closure itu berjalan sesudah middleware
        // membersihkan konteks organisasi, dan ScopeOrganisasi akan menutup
        // seluruh kueri riwayatnya sehingga kartunya terkirim kosong.
        $isi = $penyusun->pdf($aset);

        // Header dirakit HeaderUtils, bukan dengan menyambung string: nama
        // berkasnya berasal dari KodeAset yang diketik pengguna, dan tanda kutip
        // di dalamnya akan menutup parameter filename lebih awal lalu membuka
        // parameter kedua pilihan penyusun data. Seluruh ekspor lain lolos dari
        // ini karena streamDownload memanggil makeDisposition untuk mereka.
        return response($isi, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $penyusun->namaBerkas($aset),
            ),
        ]);
    }
}
