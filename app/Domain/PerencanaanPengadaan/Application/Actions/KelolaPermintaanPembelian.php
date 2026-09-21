<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\PerencanaanPengadaan\Application\Services\LayananKalkulasiPengadaan;
use App\Domain\PerencanaanPengadaan\Application\Services\LayananSaldoAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\Persetujuan\Application\Actions\AjukanPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use App\Shared\Domain\ValueObjects\Uang;
use Illuminate\Support\Str;

final class KelolaPermintaanPembelian
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNomorDokumen $nomorDokumen,
        private readonly LayananKalkulasiPengadaan $kalkulasi,
        private readonly LayananSaldoAnggaran $saldoAnggaran,
        private readonly AjukanPermintaanPersetujuan $ajukanPersetujuan,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function buat(array $data, string $penggunaId): PermintaanPembelian
    {
        return $this->transaksi->jalankan(function () use ($data, $penggunaId): PermintaanPembelian {
            $organisasiId = $this->konteks->wajibId();
            try {
                $nomor = $this->nomorDokumen->berikutnya($organisasiId, 'PermintaanPembelian');
            } catch (DataTidakDitemukan) {
                $nomor = 'PR-'.now()->format('Ym').'-'.Str::upper(Str::random(6));
            }

            $permintaan = PermintaanPembelian::create([
                'OrganisasiId' => $organisasiId,
                'Nomor' => $nomor,
                'UnitOrganisasiId' => $data['UnitOrganisasiId'] ?? null,
                'RencanaPengadaanId' => $data['RencanaPengadaanId'] ?? null,
                'PosAnggaranId' => $data['PosAnggaranId'] ?? null,
                'TanggalPermintaan' => $data['TanggalPermintaan'] ?? now()->toDateString(),
                'TanggalDibutuhkan' => $data['TanggalDibutuhkan'] ?? null,
                'Prioritas' => $data['Prioritas'] ?? 'Normal',
                'Status' => StatusPermintaanPembelian::Draft->value,
                'Alasan' => $data['Alasan'] ?? null,
                'DimintaOleh' => $penggunaId,
                'TotalEstimasi' => '0.00',
            ]);

            foreach ($data['Detail'] ?? [] as $detail) {
                $this->tambahDetail($permintaan, $detail);
            }

            $this->audit->catat('PermintaanPembelian.Dibuat', 'PermintaanPembelian', $permintaan->Id, dataSesudah: $permintaan->fresh()->toArray());

            return $permintaan->fresh();
        });
    }

    /** @param array<string, mixed> $data */
    public function tambahDetail(PermintaanPembelian $permintaan, array $data): DetailPermintaanPembelian
    {
        $this->pastikanDraft($permintaan);
        $jenis = (string) $data['JenisItem'];
        if ($jenis === 'SukuCadang' && empty($data['SukuCadangId'])) {
            throw new AturanBisnisDilanggar('Item suku cadang wajib memilih master suku cadang.');
        }
        if ($jenis === 'Aset' && empty($data['AsetReferensiId'])) {
            throw new AturanBisnisDilanggar('Item aset wajib memilih aset referensi agar kategori aset dapat diwariskan saat penerimaan.');
        }

        // Deskripsi menjadi kunci penelusuran item dari PR ke penawaran, PO, dan penerimaan aset.
        if ($permintaan->detail()->where('Deskripsi', $data['Deskripsi'])->exists()) {
            throw new AturanBisnisDilanggar('Deskripsi item harus unik dalam satu permintaan pembelian.');
        }

        $detail = DetailPermintaanPembelian::create([
            'OrganisasiId' => $permintaan->OrganisasiId,
            'PermintaanPembelianId' => $permintaan->Id,
            'JenisItem' => $jenis,
            'AsetReferensiId' => $data['AsetReferensiId'] ?? null,
            'SukuCadangId' => $data['SukuCadangId'] ?? null,
            'Deskripsi' => $data['Deskripsi'],
            'Jumlah' => $data['Jumlah'],
            'Satuan' => $data['Satuan'],
            'HargaEstimasi' => $data['HargaEstimasi'] ?? '0',
            'Spesifikasi' => $data['Spesifikasi'] ?? null,
        ]);
        $this->hitungUlang($permintaan);

        return $detail;
    }

    public function hapusDetail(PermintaanPembelian $permintaan, DetailPermintaanPembelian $detail): void
    {
        $this->pastikanDraft($permintaan);
        if ($detail->PermintaanPembelianId !== $permintaan->Id) {
            throw new AturanBisnisDilanggar('Detail bukan bagian dari permintaan pembelian ini.');
        }
        $detail->delete();
        $this->hitungUlang($permintaan);
    }

    public function submit(PermintaanPembelian $permintaan, string $penggunaId): PermintaanPembelian
    {
        $this->pastikanDraft($permintaan);

        return $this->transaksi->jalankan(function () use ($permintaan, $penggunaId): PermintaanPembelian {
            $terkunci = PermintaanPembelian::query()->lockForUpdate()->findOrFail($permintaan->Id);
            $this->hitungUlang($terkunci);
            if (! $terkunci->detail()->exists()) {
                throw new AturanBisnisDilanggar('Tambahkan minimal satu item sebelum submit.');
            }
            if ($terkunci->PosAnggaranId === null) {
                throw new AturanBisnisDilanggar('Pos anggaran wajib dipilih sebelum submit.');
            }

            $pos = PosAnggaran::query()->lockForUpdate()->findOrFail($terkunci->PosAnggaranId);
            if ($pos->anggaran()->value('Status') !== StatusAnggaran::Aktif->value) {
                throw new AturanBisnisDilanggar('Pos anggaran harus berasal dari anggaran aktif.');
            }
            $saldo = $this->saldoAnggaran->hitung($pos);
            if (Uang::dariString((string) $terkunci->TotalEstimasi)->lebihBesarDari(Uang::dariString($saldo['sisa']))) {
                throw new AturanBisnisDilanggar('Total estimasi melebihi sisa anggaran.');
            }

            $alur = AlurPersetujuan::query()->where('JenisEntitas', 'PermintaanPembelian')->where('Aktif', true)->first();
            if (! $alur) {
                throw new AturanBisnisDilanggar('Belum ada alur persetujuan aktif untuk permintaan pembelian.');
            }

            $this->ajukanPersetujuan->jalankan($alur, $terkunci->Id, ['Total' => $terkunci->TotalEstimasi], $penggunaId);
            $terkunci->Status = StatusPermintaanPembelian::MenungguPersetujuan->value;
            $terkunci->save();
            $this->audit->catat('PermintaanPembelian.Disubmit', 'PermintaanPembelian', $terkunci->Id, dataSesudah: ['Status' => $terkunci->Status, 'TotalEstimasi' => $terkunci->TotalEstimasi]);

            return $terkunci->refresh();
        });
    }

    private function hitungUlang(PermintaanPembelian $permintaan): void
    {
        $nilai = $permintaan->detail()->get()->map(fn (DetailPermintaanPembelian $detail): string => $this->kalkulasi->totalBaris(
            (string) $detail->Jumlah,
            (string) ($detail->HargaEstimasi ?? '0'),
        ));
        $permintaan->TotalEstimasi = $this->kalkulasi->jumlahkan($nilai);
        $permintaan->save();
    }

    private function pastikanDraft(PermintaanPembelian $permintaan): void
    {
        if ($permintaan->Status !== StatusPermintaanPembelian::Draft->value) {
            throw new AturanBisnisDilanggar('Hanya permintaan pembelian draft yang dapat diubah.');
        }
    }
}
