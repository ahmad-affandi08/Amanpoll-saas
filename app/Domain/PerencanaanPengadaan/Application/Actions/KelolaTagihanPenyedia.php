<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPesananPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusTagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\ValueObjects\Uang;

final class KelolaTagihanPenyedia
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function buat(PesananPembelian $po, array $data): TagihanPenyedia
    {
        if (! in_array($po->Status, [StatusPesananPembelian::DiterimaSebagian->value, StatusPesananPembelian::DiterimaPenuh->value], true)) {
            throw new AturanBisnisDilanggar('Tagihan hanya dapat dicatat setelah ada penerimaan barang.');
        }

        return $this->transaksi->jalankan(function () use ($po, $data): TagihanPenyedia {
            $terkunci = PesananPembelian::query()->lockForUpdate()->findOrFail($po->Id);
            $subtotal = Uang::dariString((string) $data['Subtotal']);
            $pajak = Uang::dariString((string) ($data['Pajak'] ?? '0'));
            $total = $subtotal->tambah($pajak);
            $batasDiterima = $this->nilaiDiterima($terkunci);
            $sudahDitagih = Uang::dariString((string) TagihanPenyedia::query()->where('PesananPembelianId', $terkunci->Id)->sum('Total'));
            if ($sudahDitagih->tambah($total)->lebihBesarDari($batasDiterima)) {
                throw new AturanBisnisDilanggar('Total tagihan melampaui nilai barang yang sudah diterima.');
            }

            $tagihan = TagihanPenyedia::create([
                'OrganisasiId' => $terkunci->OrganisasiId,
                'PenyediaId' => $terkunci->PenyediaId,
                'PesananPembelianId' => $terkunci->Id,
                'NomorTagihan' => $data['NomorTagihan'],
                'TanggalTagihan' => $data['TanggalTagihan'],
                'JatuhTempo' => $data['JatuhTempo'] ?? null,
                'Subtotal' => $subtotal->keString(),
                'Pajak' => $pajak->keString(),
                'Total' => $total->keString(),
                'Sisa' => $total->keString(),
                'Status' => StatusTagihanPenyedia::BelumDibayar->value,
            ]);
            $this->audit->catat('TagihanPenyedia.Dicatat', 'TagihanPenyedia', $tagihan->Id, dataSesudah: array_merge($tagihan->toArray(), ['NilaiDiterima' => $batasDiterima->keString()]));

            return $tagihan;
        });
    }

    private function nilaiDiterima(PesananPembelian $po): Uang
    {
        $totalMinor = 0;
        foreach ($po->detail()->get() as $detail) {
            /** @var DetailPesananPembelian $detail */
            $diterima = $this->skalaEmpat((string) DetailPenerimaanPembelian::query()->where('DetailPesananPembelianId', $detail->Id)->sum('JumlahDiterima'));
            $dipesan = $this->skalaEmpat((string) $detail->Jumlah);
            if ($dipesan > 0) {
                $totalBarisMinor = Uang::dariString((string) $detail->Total)->nilaiMinor();
                $totalMinor += intdiv(($totalBarisMinor * $diterima) + intdiv($dipesan, 2), $dipesan);
            }
        }

        return Uang::dariMinor($totalMinor);
    }

    private function skalaEmpat(string $nilai): int
    {
        [$bulat, $pecahan] = array_pad(explode('.', $nilai, 2), 2, '');

        return ((int) $bulat * 10000) + (int) substr(str_pad($pecahan, 4, '0'), 0, 4);
    }
}
