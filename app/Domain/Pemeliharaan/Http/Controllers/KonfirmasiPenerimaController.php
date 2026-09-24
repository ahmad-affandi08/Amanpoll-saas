<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gambar tanda tangan yang dicap pada sebuah konfirmasi penerima (PRD 8.22).
 *
 * Diizinkan hanya bagi yang boleh melihat perintah kerjanya, dan hanya berkas yang
 * dirujuk konfirmasi itu. Tidak ada rute umum untuk membuka tanda tangan profil
 * orang lain: berkas profil yang tidak dicap ke konfirmasi mana pun tidak terjangkau.
 */
final class KonfirmasiPenerimaController extends Controller
{
    public function tandaTangan(PerintahKerja $perintahKerja, string $konfirmasi, PenyimpanBerkas $penyimpanBerkas): StreamedResponse
    {
        $this->authorize('view', $perintahKerja);

        $berkasId = KonfirmasiPenerimaPerintahKerja::query()
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->whereKey($konfirmasi)
            ->value('TandaTanganBerkasId');
        $berkas = is_string($berkasId) ? Berkas::query()->withTrashed()->find($berkasId) : null;

        abort_unless($berkas instanceof Berkas, 404);

        return $penyimpanBerkas->responsThumbnail($berkas);
    }
}
