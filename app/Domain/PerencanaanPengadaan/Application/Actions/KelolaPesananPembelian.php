<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use App\Domain\Persetujuan\Application\Actions\AjukanPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Support\Str;

final class KelolaPesananPembelian
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNomorDokumen $nomorDokumen,
        private readonly AjukanPermintaanPersetujuan $ajukanPersetujuan,
        private readonly CatatTransaksiAnggaran $catatTransaksiAnggaran,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function buatDariPenawaran(PenawaranPenyedia $penawaran, array $data, string $penggunaId): PesananPembelian
    {
        if ($penawaran->Status !== PenawaranPenyedia::STATUS_TERPILIH) {
            throw new AturanBisnisDilanggar('PO hanya dapat dibuat dari penawaran terpilih.');
        }
        if (PesananPembelian::query()->where('PenawaranPenyediaId', $penawaran->Id)->exists()) {
            throw new AturanBisnisDilanggar('Penawaran ini sudah memiliki pesanan pembelian.');
        }

        return $this->transaksi->jalankan(function () use ($penawaran, $data, $penggunaId): PesananPembelian {
            $penawaran->loadMissing(['permintaanPenawaran.permintaanPembelian', 'detail.detailPermintaanPembelian']);
            $permintaan = $penawaran->permintaanPenawaran->permintaanPembelian;
            try {
                $nomor = $this->nomorDokumen->berikutnya($this->konteks->wajibId(), 'PesananPembelian');
            } catch (DataTidakDitemukan) {
                $nomor = 'PO-'.now()->format('Ym').'-'.Str::upper(Str::random(6));
            }

            $po = PesananPembelian::create([
                'OrganisasiId' => $penawaran->OrganisasiId,
                'Nomor' => $nomor,
                'PenyediaId' => $penawaran->PenyediaId,
                'PermintaanPembelianId' => $permintaan->Id,
                'PenawaranPenyediaId' => $penawaran->Id,
                'PosAnggaranId' => $permintaan->PosAnggaranId,
                'TanggalPesanan' => $data['TanggalPesanan'] ?? now()->toDateString(),
                'TanggalKirimRencana' => $data['TanggalKirimRencana'] ?? null,
                'MataUang' => $penawaran->MataUang,
                'Subtotal' => $penawaran->Subtotal,
                'Pajak' => $penawaran->Pajak,
                'Diskon' => $penawaran->Diskon,
                'Total' => $penawaran->Total,
                'Status' => PesananPembelian::STATUS_DRAFT,
                'Catatan' => $data['Catatan'] ?? null,
                'DibuatOleh' => $penggunaId,
            ]);

            foreach ($penawaran->detail as $baris) {
                /** @var DetailPenawaranPenyedia $baris */
                $asal = $baris->detailPermintaanPembelian;
                DetailPesananPembelian::create([
                    'OrganisasiId' => $po->OrganisasiId,
                    'PesananPembelianId' => $po->Id,
                    'JenisItem' => $asal->JenisItem,
                    'SukuCadangId' => $asal->SukuCadangId,
                    'Deskripsi' => $baris->Deskripsi,
                    'Jumlah' => $baris->Jumlah,
                    'Satuan' => $asal->Satuan,
                    'HargaSatuan' => $baris->HargaSatuan,
                    'Diskon' => $baris->Diskon,
                    'Pajak' => $baris->Pajak,
                    'Total' => $baris->Total,
                ]);
            }

            $this->audit->catat('PesananPembelian.Dibuat', 'PesananPembelian', $po->Id, dataSesudah: $po->toArray());

            return $po;
        });
    }

    public function ajukan(PesananPembelian $po, string $penggunaId): PesananPembelian
    {
        if ($po->Status !== PesananPembelian::STATUS_DRAFT || ! $po->detail()->exists()) {
            throw new AturanBisnisDilanggar('Hanya PO draft dengan detail yang dapat diajukan.');
        }
        $alur = AlurPersetujuan::query()->where('JenisEntitas', 'PesananPembelian')->where('Aktif', true)->first();
        if (! $alur) {
            throw new AturanBisnisDilanggar('Belum ada alur persetujuan aktif untuk pesanan pembelian.');
        }

        $this->ajukanPersetujuan->jalankan($alur, $po->Id, ['Total' => $po->Total], $penggunaId);
        $po->Status = PesananPembelian::STATUS_MENUNGGU_PERSETUJUAN;
        $po->save();

        return $po->refresh();
    }

    public function kirim(PesananPembelian $po): PesananPembelian
    {
        if ($po->Status !== PesananPembelian::STATUS_DISETUJUI || $po->PosAnggaranId === null) {
            throw new AturanBisnisDilanggar('Hanya PO yang disetujui dan memiliki pos anggaran yang dapat dikirim.');
        }

        return $this->transaksi->jalankan(function () use ($po): PesananPembelian {
            $terkunci = PesananPembelian::query()->lockForUpdate()->findOrFail($po->Id);
            if (! TransaksiAnggaran::query()->where('ReferensiJenis', 'PesananPembelian')->where('ReferensiId', $terkunci->Id)->where('Jenis', TransaksiAnggaran::JENIS_KOMITMEN)->exists()) {
                $this->catatTransaksiAnggaran->jalankan($terkunci->posAnggaran()->firstOrFail(), [
                    'Jenis' => TransaksiAnggaran::JENIS_KOMITMEN,
                    'Jumlah' => $terkunci->Total,
                    'Tanggal' => now()->toDateString(),
                    'ReferensiJenis' => 'PesananPembelian',
                    'ReferensiId' => $terkunci->Id,
                    'Keterangan' => "Komitmen PO {$terkunci->Nomor}",
                ]);
            }
            $terkunci->Status = PesananPembelian::STATUS_DIKIRIM;
            $terkunci->save();
            $this->audit->catat('PesananPembelian.Dikirim', 'PesananPembelian', $terkunci->Id, dataSesudah: ['Status' => $terkunci->Status]);

            return $terkunci->refresh();
        });
    }
}
