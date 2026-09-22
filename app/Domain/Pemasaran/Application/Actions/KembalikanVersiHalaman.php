<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiHalamanPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

/**
 * Rollback ke versi halaman sebelumnya (MARKETING.md 8).
 *
 * Isi versi lama disalin menjadi versi baru, bukan ditunjuk kembali. Dengan
 * begitu nomor versi selalu maju dan riwayatnya terbaca lurus: "versi 5 adalah
 * salinan versi 2" jauh lebih mudah ditelusuri daripada penunjuk yang melompat
 * mundur dan menghapus jejak bahwa versi 3 dan 4 pernah terbit.
 */
final class KembalikanVersiHalaman
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly SimpanDrafHalaman $draf,
        private readonly TerbitkanHalaman $terbitkan,
        private readonly LayananAudit $audit,
    ) {}

    public function jalankan(HalamanPemasaran $halaman, VersiHalamanPemasaran $sumber): HalamanPemasaran
    {
        return $this->transaksi->jalankan(function () use ($halaman, $sumber): HalamanPemasaran {
            if ($sumber->HalamanPemasaranId !== $halaman->Id) {
                throw new AturanBisnisDilanggar('Versi tersebut bukan milik halaman ini.');
            }

            if ($sumber->Id === $halaman->VersiTerbitId) {
                throw new AturanBisnisDilanggar('Versi tersebut sudah menjadi versi yang terbit.');
            }

            $salinan = $this->salin($halaman, $sumber);
            $this->draf->salinBlok($sumber, $salinan);

            $halaman->VersiDrafId = $salinan->Id;
            $halaman->save();

            $this->audit->catat(
                'HalamanPemasaran.Dikembalikan',
                'HalamanPemasaran',
                $halaman->Id,
                dataSebelum: ['VersiTerbitId' => $halaman->VersiTerbitId],
                dataSesudah: [
                    'DisalinDariNomor' => $sumber->Nomor,
                    'NomorBaru' => $salinan->Nomor,
                ],
            );

            /*
             * Rollback halaman yang sedang terbit langsung menerbitkan
             * salinannya: yang diminta adalah situs publik kembali seperti
             * semula, bukan sebuah draf yang masih menunggu disetujui.
             * Halaman yang belum terbit hanya mendapat drafnya.
             */
            if ($halaman->Status === StatusHalamanPemasaran::Terbit) {
                return $this->terbitkan->jalankan($halaman, $salinan);
            }

            return $halaman;
        });
    }

    private function salin(HalamanPemasaran $halaman, VersiHalamanPemasaran $sumber): VersiHalamanPemasaran
    {
        $nomorTerakhir = (int) VersiHalamanPemasaran::query()
            ->where('HalamanPemasaranId', $halaman->Id)
            ->max('Nomor');

        return VersiHalamanPemasaran::create([
            'HalamanPemasaranId' => $halaman->Id,
            'Nomor' => $nomorTerakhir + 1,
            'Judul' => $sumber->Judul,
            'MetaJudul' => $sumber->MetaJudul,
            'MetaDeskripsi' => $sumber->MetaDeskripsi,
            'Kanonik' => $sumber->Kanonik,
            'OgJudul' => $sumber->OgJudul,
            'OgDeskripsi' => $sumber->OgDeskripsi,
            'OgGambar' => $sumber->OgGambar,
            'SkemaTipe' => $sumber->SkemaTipe,
            'Catatan' => "Salinan versi {$sumber->Nomor}.",
            'DibuatOlehPlatformId' => Auth::guard('platform')->id(),
            'DibuatPada' => CarbonImmutable::now(),
        ]);
    }
}
