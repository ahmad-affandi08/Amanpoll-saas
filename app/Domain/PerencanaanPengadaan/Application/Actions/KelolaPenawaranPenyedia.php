<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\PerencanaanPengadaan\Application\Services\LayananKalkulasiPengadaan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class KelolaPenawaranPenyedia
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananKalkulasiPengadaan $kalkulasi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function catat(PermintaanPenawaran $rfq, array $data): PenawaranPenyedia
    {
        if ($rfq->Status !== PermintaanPenawaran::STATUS_DIBUKA) {
            throw new AturanBisnisDilanggar('Penawaran hanya dapat dicatat pada RFQ yang sedang dibuka.');
        }
        if ($rfq->BatasPenawaran !== null && now()->isAfter($rfq->BatasPenawaran)) {
            throw new AturanBisnisDilanggar('Batas waktu penawaran sudah berakhir.');
        }
        if (! $rfq->penyediaDiundang()->where('PenyediaId', $data['PenyediaId'])->exists()) {
            throw new AturanBisnisDilanggar('Penyedia tidak termasuk dalam undangan RFQ ini.');
        }

        return $this->transaksi->jalankan(function () use ($rfq, $data): PenawaranPenyedia {
            $penawaran = PenawaranPenyedia::create([
                'OrganisasiId' => $rfq->OrganisasiId,
                'PermintaanPenawaranId' => $rfq->Id,
                'PenyediaId' => $data['PenyediaId'],
                'NomorPenawaran' => $data['NomorPenawaran'] ?? null,
                'TanggalPenawaran' => $data['TanggalPenawaran'],
                'BerlakuSampai' => $data['BerlakuSampai'] ?? null,
                'MataUang' => $data['MataUang'] ?? 'IDR',
                'Subtotal' => '0.00',
                'Pajak' => '0.00',
                'Diskon' => '0.00',
                'Total' => '0.00',
                'Status' => PenawaranPenyedia::STATUS_DIAJUKAN,
                'Catatan' => $data['Catatan'] ?? null,
            ]);

            foreach ($data['Detail'] as $baris) {
                $detailPermintaan = $rfq->permintaanPembelian->detail()->whereKey($baris['DetailPermintaanPembelianId'])->firstOrFail();
                $total = $this->kalkulasi->totalBaris(
                    (string) $baris['Jumlah'],
                    (string) $baris['HargaSatuan'],
                    (string) ($baris['Diskon'] ?? '0'),
                    (string) ($baris['Pajak'] ?? '0'),
                );
                DetailPenawaranPenyedia::create([
                    'OrganisasiId' => $rfq->OrganisasiId,
                    'PenawaranPenyediaId' => $penawaran->Id,
                    'DetailPermintaanPembelianId' => $detailPermintaan->Id,
                    'Deskripsi' => $detailPermintaan->Deskripsi,
                    'Jumlah' => $baris['Jumlah'],
                    'HargaSatuan' => $baris['HargaSatuan'],
                    'Diskon' => $baris['Diskon'] ?? '0',
                    'Pajak' => $baris['Pajak'] ?? '0',
                    'Total' => $total,
                    'WaktuPengirimanHari' => $baris['WaktuPengirimanHari'] ?? null,
                ]);
            }

            $this->hitungUlang($penawaran);
            $this->audit->catat('PenawaranPenyedia.Dicatat', 'PenawaranPenyedia', $penawaran->Id, dataSesudah: $penawaran->fresh()->toArray());

            return $penawaran->fresh();
        });
    }

    public function pilih(PenawaranPenyedia $penawaran): PenawaranPenyedia
    {
        return $this->transaksi->jalankan(function () use ($penawaran): PenawaranPenyedia {
            $terkunci = PenawaranPenyedia::query()->lockForUpdate()->findOrFail($penawaran->Id);
            $rfq = PermintaanPenawaran::query()->lockForUpdate()->findOrFail($terkunci->PermintaanPenawaranId);
            if ($rfq->Status !== PermintaanPenawaran::STATUS_DIBUKA || $terkunci->Status !== PenawaranPenyedia::STATUS_DIAJUKAN) {
                throw new AturanBisnisDilanggar('Hanya penawaran aktif pada RFQ terbuka yang dapat dipilih.');
            }

            PenawaranPenyedia::query()->where('PermintaanPenawaranId', $rfq->Id)->whereKeyNot($terkunci->Id)->update(['Status' => PenawaranPenyedia::STATUS_DITOLAK]);
            $terkunci->Status = PenawaranPenyedia::STATUS_TERPILIH;
            $terkunci->save();
            $rfq->Status = PermintaanPenawaran::STATUS_DITUTUP;
            $rfq->save();
            $this->audit->catat('PenawaranPenyedia.Dipilih', 'PenawaranPenyedia', $terkunci->Id, dataSesudah: ['Status' => $terkunci->Status, 'Total' => $terkunci->Total]);

            return $terkunci->refresh();
        });
    }

    private function hitungUlang(PenawaranPenyedia $penawaran): void
    {
        $detail = $penawaran->detail()->get();
        $penawaran->Subtotal = $this->kalkulasi->jumlahkan($detail->map(fn (DetailPenawaranPenyedia $baris): string => $this->kalkulasi->totalBaris((string) $baris->Jumlah, (string) $baris->HargaSatuan)));
        $penawaran->Diskon = $this->kalkulasi->jumlahkan($detail->pluck('Diskon')->map(fn ($nilai): string => (string) $nilai));
        $penawaran->Pajak = $this->kalkulasi->jumlahkan($detail->pluck('Pajak')->map(fn ($nilai): string => (string) $nilai));
        $penawaran->Total = $this->kalkulasi->jumlahkan($detail->pluck('Total')->map(fn ($nilai): string => (string) $nilai));
        $penawaran->save();
    }
}
