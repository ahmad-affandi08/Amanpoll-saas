<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kolaborasi\Application\Actions\LampirkanBerkas;
use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Menambah foto ke galeri aset (PRD 8.4 "Foto Aset").
 *
 * Foto disimpan lewat PenyimpanBerkas (kompresi + thumbnail, PRD 11.1), lalu
 * dilampirkan berkategori `FotoAset`. Batas galeri diperiksa ulang di bawah
 * kunci baris aset, sehingga dua unggahan serentak tidak dapat melewati
 * batasnya. Foto pertama otomatis menjadi foto utama. Bila penolakan terjadi
 * sesudah berkas tertulis, berkasnya dihapus lagi.
 */
final class TambahFotoAset
{
    public function __construct(
        private readonly PenyimpanBerkas $penyimpan,
        private readonly LampirkanBerkas $lampirkan,
        private readonly GaleriFotoAset $galeri,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  list<UploadedFile>  $foto
     * @return list<Berkas>
     */
    public function jalankan(Aset $aset, array $foto, ?string $pengunggahId): array
    {
        if ($foto === []) {
            return [];
        }

        // Pemeriksaan awal supaya berkas tidak ditulis bila jelas akan ditolak.
        $this->pastikanMuat($this->galeri->jumlah($aset), count($foto));

        $berkas = [];

        try {
            foreach ($foto as $satu) {
                $berkas[] = $this->penyimpan->simpanUnggahan($satu, $pengunggahId);
            }

            [$utamaSebelum, $utamaSesudah] = $this->transaksi->jalankan(function () use ($aset, $berkas, $pengunggahId): array {
                $terkunci = Aset::query()->whereKey($aset->Id)->lockForUpdate()->firstOrFail();
                $this->pastikanMuat($this->galeri->jumlah($terkunci), count($berkas));

                $utama = $terkunci->FotoUtamaBerkasId;
                $utamaSah = $utama !== null && $this->galeri->kueri($terkunci->Id)->where('BerkasId', $utama)->exists();

                foreach ($berkas as $satu) {
                    $this->lampirkan->jalankan(GaleriFotoAset::JENIS_ENTITAS, $terkunci->Id, $satu->Id, GaleriFotoAset::KATEGORI, null, $pengunggahId);
                }

                if (! $utamaSah) {
                    Aset::query()->whereKey($terkunci->Id)->update(['FotoUtamaBerkasId' => $berkas[0]->Id]);

                    return [$utama, $berkas[0]->Id];
                }

                return [$utama, $utama];
            });
        } catch (Throwable $galat) {
            foreach ($berkas as $satu) {
                $this->penyimpan->hapus($satu);
            }

            throw $galat;
        }

        $aset->setAttribute('FotoUtamaBerkasId', $utamaSesudah);
        $aset->syncOriginalAttribute('FotoUtamaBerkasId');

        $this->audit->catat(
            'Aset.FotoDitambahkan',
            'Aset',
            $aset->Id,
            dataSebelum: ['FotoUtamaBerkasId' => $utamaSebelum],
            dataSesudah: [
                'BerkasId' => array_map(fn (Berkas $satu): string => $satu->Id, $berkas),
                'FotoUtamaBerkasId' => $utamaSesudah,
            ],
        );

        return $berkas;
    }

    private function pastikanMuat(int $jumlahSekarang, int $jumlahBaru): void
    {
        if ($jumlahSekarang + $jumlahBaru <= GaleriFotoAset::MAKS_FOTO) {
            return;
        }

        $sisa = max(0, GaleriFotoAset::MAKS_FOTO - $jumlahSekarang);

        throw new AturanBisnisDilanggar($sisa === 0
            ? 'Galeri aset ini sudah berisi '.GaleriFotoAset::MAKS_FOTO.' foto, batas paling banyak. Hapus foto lama lebih dulu.'
            : 'Galeri aset paling banyak '.GaleriFotoAset::MAKS_FOTO." foto; tersisa tempat untuk {$sisa} foto lagi.");
    }
}
