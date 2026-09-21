<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenilaianUsulanAset;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use App\Domain\Persetujuan\Application\Actions\AjukanPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Support\Str;

final class KelolaUsulanAset
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNomorDokumen $nomorDokumen,
        private readonly AjukanPermintaanPersetujuan $ajukanPersetujuan,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data, string $penggunaId): UsulanAset
    {
        return $this->transaksi->jalankan(function () use ($data, $penggunaId): UsulanAset {
            $organisasiId = $this->konteksOrganisasi->wajibId();
            $nomor = $data['Nomor'] ?? null;
            if (! is_string($nomor) || trim($nomor) === '') {
                try {
                    $nomor = $this->nomorDokumen->berikutnya($organisasiId, 'UsulanAset');
                } catch (DataTidakDitemukan) {
                    $nomor = 'USL-'.now()->format('Ym').'-'.Str::upper(Str::random(6));
                }
            }

            $usulan = UsulanAset::create([
                'OrganisasiId' => $organisasiId,
                'Nomor' => $nomor,
                'UnitOrganisasiId' => $data['UnitOrganisasiId'],
                'KategoriAsetId' => $data['KategoriAsetId'] ?? null,
                'ModelAsetId' => $data['ModelAsetId'] ?? null,
                'NamaKebutuhan' => $data['NamaKebutuhan'],
                'Jumlah' => $data['Jumlah'],
                'EstimasiHargaSatuan' => $data['EstimasiHargaSatuan'] ?? null,
                'Alasan' => $data['Alasan'],
                'JenisKebutuhan' => $data['JenisKebutuhan'] ?? null,
                'TahunKebutuhan' => $data['TahunKebutuhan'] ?? null,
                'Prioritas' => $data['Prioritas'] ?? UsulanAset::PRIORITAS_NORMAL,
                'Status' => UsulanAset::STATUS_DRAFT,
                'DiajukanOleh' => $penggunaId,
            ]);

            $this->audit->catat('UsulanAset.Dibuat', 'UsulanAset', $usulan->Id, dataSesudah: $usulan->toArray());

            return $usulan;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(UsulanAset $usulan, array $data): UsulanAset
    {
        if (! in_array($usulan->Status, [UsulanAset::STATUS_DRAFT, UsulanAset::STATUS_DITOLAK], true)) {
            throw new AturanBisnisDilanggar('Hanya usulan draft atau ditolak yang dapat diubah.');
        }

        $sebelum = $usulan->toArray();
        $usulan->fill([
            'UnitOrganisasiId' => $data['UnitOrganisasiId'] ?? $usulan->UnitOrganisasiId,
            'KategoriAsetId' => array_key_exists('KategoriAsetId', $data) ? $data['KategoriAsetId'] : $usulan->KategoriAsetId,
            'ModelAsetId' => array_key_exists('ModelAsetId', $data) ? $data['ModelAsetId'] : $usulan->ModelAsetId,
            'NamaKebutuhan' => $data['NamaKebutuhan'] ?? $usulan->NamaKebutuhan,
            'Jumlah' => $data['Jumlah'] ?? $usulan->Jumlah,
            'EstimasiHargaSatuan' => array_key_exists('EstimasiHargaSatuan', $data) ? $data['EstimasiHargaSatuan'] : $usulan->EstimasiHargaSatuan,
            'Alasan' => $data['Alasan'] ?? $usulan->Alasan,
            'JenisKebutuhan' => array_key_exists('JenisKebutuhan', $data) ? $data['JenisKebutuhan'] : $usulan->JenisKebutuhan,
            'TahunKebutuhan' => array_key_exists('TahunKebutuhan', $data) ? $data['TahunKebutuhan'] : $usulan->TahunKebutuhan,
            'Prioritas' => $data['Prioritas'] ?? $usulan->Prioritas,
            'Status' => UsulanAset::STATUS_DRAFT,
        ])->save();

        $this->audit->catat('UsulanAset.Diperbarui', 'UsulanAset', $usulan->Id, $sebelum, $usulan->fresh()->toArray());

        return $usulan->fresh();
    }

    public function submit(UsulanAset $usulan): UsulanAset
    {
        if ($usulan->Status !== UsulanAset::STATUS_DRAFT) {
            throw new AturanBisnisDilanggar('Hanya usulan draft yang dapat disubmit.');
        }

        $usulan->forceFill([
            'Status' => UsulanAset::STATUS_DIAJUKAN,
            'DiajukanPada' => now(),
        ])->save();

        $this->audit->catat('UsulanAset.Disubmit', 'UsulanAset', $usulan->Id, dataSesudah: ['Status' => $usulan->Status]);

        return $usulan->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function nilai(UsulanAset $usulan, array $data, string $penggunaId): PenilaianUsulanAset
    {
        if ($usulan->Status !== UsulanAset::STATUS_DIAJUKAN) {
            throw new AturanBisnisDilanggar('Penilaian hanya dapat dicatat pada usulan yang telah disubmit.');
        }

        $penilaian = PenilaianUsulanAset::create([
            'OrganisasiId' => $usulan->OrganisasiId,
            'UsulanAsetId' => $usulan->Id,
            'Kriteria' => $data['Kriteria'],
            'Bobot' => $data['Bobot'],
            'Nilai' => $data['Nilai'],
            'Skor' => $this->kalikanEmpatDesimal((string) $data['Bobot'], (string) $data['Nilai']),
            'DinilaiOleh' => $penggunaId,
            'DinilaiPada' => now(),
        ]);

        if (isset($data['Prioritas'])) {
            $usulan->Prioritas = $data['Prioritas'];
            $usulan->save();
        }

        $this->audit->catat('UsulanAset.Dinilai', 'UsulanAset', $usulan->Id, dataSesudah: $penilaian->toArray());

        return $penilaian;
    }

    public function ajukanPersetujuan(UsulanAset $usulan, string $penggunaId): UsulanAset
    {
        if ($usulan->Status !== UsulanAset::STATUS_DIAJUKAN) {
            throw new AturanBisnisDilanggar('Hanya usulan yang telah dinilai yang dapat diajukan untuk persetujuan.');
        }

        if (! $usulan->penilaian()->exists()) {
            throw new AturanBisnisDilanggar('Tambahkan minimal satu penilaian sebelum mengajukan persetujuan.');
        }

        $alur = AlurPersetujuan::query()
            ->where('JenisEntitas', 'UsulanAset')
            ->where('Aktif', true)
            ->first();

        if (! $alur) {
            throw new AturanBisnisDilanggar('Belum ada alur persetujuan aktif untuk usulan aset.');
        }

        return $this->transaksi->jalankan(function () use ($usulan, $penggunaId, $alur): UsulanAset {
            $this->ajukanPersetujuan->jalankan($alur, $usulan->Id, ['Prioritas' => $usulan->Prioritas], $penggunaId);
            $usulan->Status = UsulanAset::STATUS_MENUNGGU_PERSETUJUAN;
            $usulan->save();

            $this->audit->catat('UsulanAset.DiajukanUntukPersetujuan', 'UsulanAset', $usulan->Id, dataSesudah: ['Status' => $usulan->Status]);

            return $usulan->refresh();
        });
    }

    public function hapus(UsulanAset $usulan): void
    {
        if (! in_array($usulan->Status, [UsulanAset::STATUS_DRAFT, UsulanAset::STATUS_DITOLAK], true)) {
            throw new AturanBisnisDilanggar('Hanya usulan draft atau ditolak yang dapat dihapus.');
        }

        $sebelum = $usulan->toArray();
        $usulan->delete();
        $this->audit->catat('UsulanAset.Dihapus', 'UsulanAset', $usulan->Id, dataSebelum: $sebelum);
    }

    private function kalikanEmpatDesimal(string $nilaiPertama, string $nilaiKedua): string
    {
        $pertama = $this->keBilanganSkalaEmpat($nilaiPertama);
        $kedua = $this->keBilanganSkalaEmpat($nilaiKedua);
        $hasilSkalaDelapan = $pertama * $kedua;
        $hasilSkalaEmpat = intdiv($hasilSkalaDelapan + 5000, 10000);

        return intdiv($hasilSkalaEmpat, 10000).'.'.str_pad((string) ($hasilSkalaEmpat % 10000), 4, '0', STR_PAD_LEFT);
    }

    private function keBilanganSkalaEmpat(string $nilai): int
    {
        [$bulat, $pecahan] = array_pad(explode('.', $nilai, 2), 2, '');

        return ((int) $bulat * 10000) + (int) substr(str_pad($pecahan, 4, '0'), 0, 4);
    }
}
