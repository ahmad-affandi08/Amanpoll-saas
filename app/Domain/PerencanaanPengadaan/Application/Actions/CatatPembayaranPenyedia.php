<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PembayaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\ValueObjects\Uang;

final class CatatPembayaranPenyedia
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly CatatTransaksiAnggaran $catatTransaksiAnggaran,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function jalankan(TagihanPenyedia $tagihan, array $data, string $penggunaId): PembayaranPenyedia
    {
        return $this->transaksi->jalankan(function () use ($tagihan, $data, $penggunaId): PembayaranPenyedia {
            $terkunci = TagihanPenyedia::query()->lockForUpdate()->findOrFail($tagihan->Id);
            $jumlah = Uang::dariString((string) $data['Jumlah']);
            $sisa = Uang::dariString((string) $terkunci->Sisa);
            if ($jumlah->nilaiMinor() <= 0 || $jumlah->lebihBesarDari($sisa)) {
                throw new AturanBisnisDilanggar('Jumlah pembayaran harus positif dan tidak boleh melebihi sisa tagihan.');
            }

            $pembayaran = PembayaranPenyedia::create([
                'OrganisasiId' => $terkunci->OrganisasiId,
                'TagihanPenyediaId' => $terkunci->Id,
                'NomorPembayaran' => $data['NomorPembayaran'],
                'TanggalBayar' => $data['TanggalBayar'],
                'Jumlah' => $jumlah->keString(),
                'Metode' => $data['Metode'],
                'Referensi' => $data['Referensi'] ?? null,
                'DibuatOleh' => $penggunaId,
            ]);

            $sisaBaru = $sisa->kurang($jumlah);
            $terkunci->Sisa = $sisaBaru->keString();
            $terkunci->Status = $sisaBaru->nilaiMinor() === 0
                ? TagihanPenyedia::STATUS_DIBAYAR
                : TagihanPenyedia::STATUS_DIBAYAR_SEBAGIAN;
            $terkunci->save();
            $this->catatRealisasiAnggaran($terkunci, $pembayaran);
            $this->audit->catat('PembayaranPenyedia.Dicatat', 'PembayaranPenyedia', $pembayaran->Id, dataSesudah: array_merge($pembayaran->toArray(), ['SisaTagihan' => $sisaBaru->keString()]));

            return $pembayaran;
        });
    }

    /** Pembayaran mengubah komitmen PO menjadi realisasi anggaran pada pos yang sama. */
    private function catatRealisasiAnggaran(TagihanPenyedia $tagihan, PembayaranPenyedia $pembayaran): void
    {
        $pesanan = $tagihan->pesananPembelian()->first();
        if ($pesanan === null || $pesanan->PosAnggaranId === null) {
            return;
        }

        $this->catatTransaksiAnggaran->jalankan($pesanan->posAnggaran()->firstOrFail(), [
            'Jenis' => TransaksiAnggaran::JENIS_REALISASI,
            'Jumlah' => $pembayaran->Jumlah,
            'Tanggal' => $pembayaran->TanggalBayar->toDateString(),
            'ReferensiJenis' => 'PembayaranPenyedia',
            'ReferensiId' => $pembayaran->Id,
            'Keterangan' => "Realisasi pembayaran {$pembayaran->NomorPembayaran} atas PO {$pesanan->Nomor}",
        ]);
    }
}
