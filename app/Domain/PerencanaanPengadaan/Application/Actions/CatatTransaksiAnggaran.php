<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\PerencanaanPengadaan\Application\Services\LayananSaldoAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\JenisTransaksiAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\ValueObjects\Uang;

final class CatatTransaksiAnggaran
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananSaldoAnggaran $layananSaldo,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(PosAnggaran $posAnggaran, array $data): TransaksiAnggaran
    {
        return $this->transaksi->jalankan(function () use ($posAnggaran, $data): TransaksiAnggaran {
            $pos = PosAnggaran::query()->lockForUpdate()->findOrFail($posAnggaran->Id);
            $anggaran = $pos->anggaran()->firstOrFail();

            if ($anggaran->Status !== StatusAnggaran::Aktif->value) {
                throw new AturanBisnisDilanggar('Transaksi hanya dapat dicatat pada anggaran aktif.');
            }

            $jenis = (string) $data['Jenis'];
            $jumlah = Uang::dariString((string) $data['Jumlah']);
            $saldo = $this->layananSaldo->hitung($pos);
            $sisa = Uang::dariString($saldo['sisa']);
            $ditahan = Uang::dariString($saldo['ditahan']);
            $terpakai = Uang::dariString($saldo['terpakai']);

            $this->pastikanValid($jenis, $jumlah, $sisa, $ditahan, $terpakai, (string) ($data['Keterangan'] ?? ''));

            $transaksi = $this->buatTransaksi($pos, $jenis, $jumlah->keString(), $data);

            if ($jenis === JenisTransaksiAnggaran::Realisasi->value && $ditahan->nilaiMinor() > 0) {
                $jumlahPelepasanMinor = min($jumlah->nilaiMinor(), $ditahan->nilaiMinor());
                $this->buatTransaksi(
                    $pos,
                    JenisTransaksiAnggaran::PelepasanKomitmen->value,
                    Uang::dariMinor($jumlahPelepasanMinor)->keString(),
                    array_merge($data, ['Keterangan' => 'Pelepasan otomatis saat realisasi.']),
                );
            }

            $saldoBaru = $this->layananSaldo->rekonsiliasi($pos);
            $this->audit->catat(
                'TransaksiAnggaran.Dicatat',
                'TransaksiAnggaran',
                $transaksi->Id,
                dataSesudah: array_merge($transaksi->toArray(), ['SaldoPos' => $saldoBaru]),
            );

            return $transaksi;
        });
    }

    private function pastikanValid(string $jenis, Uang $jumlah, Uang $sisa, Uang $ditahan, Uang $terpakai, string $keterangan): void
    {
        if (JenisTransaksiAnggaran::tryFrom($jenis) === null) {
            throw new AturanBisnisDilanggar('Jenis transaksi anggaran tidak dikenal.');
        }

        if ($jumlah->nilaiMinor() === 0 || ($jenis !== JenisTransaksiAnggaran::Penyesuaian->value && $jumlah->nilaiMinor() < 0)) {
            throw new AturanBisnisDilanggar('Jumlah transaksi harus lebih besar dari nol.');
        }

        if ($jenis === JenisTransaksiAnggaran::Komitmen->value && $jumlah->lebihBesarDari($sisa)) {
            throw new AturanBisnisDilanggar('Komitmen melebihi sisa anggaran yang tersedia.');
        }

        if ($jenis === JenisTransaksiAnggaran::PelepasanKomitmen->value && $jumlah->lebihBesarDari($ditahan)) {
            throw new AturanBisnisDilanggar('Pelepasan melebihi komitmen yang masih ditahan.');
        }

        if ($jenis === JenisTransaksiAnggaran::Realisasi->value) {
            $bagianTanpaKomitmen = max(0, $jumlah->nilaiMinor() - $ditahan->nilaiMinor());
            if ($bagianTanpaKomitmen > $sisa->nilaiMinor()) {
                throw new AturanBisnisDilanggar('Realisasi melebihi sisa anggaran yang tersedia.');
            }
        }

        if ($jenis === JenisTransaksiAnggaran::Penyesuaian->value) {
            if (trim($keterangan) === '') {
                throw new AturanBisnisDilanggar('Alasan penyesuaian anggaran wajib diisi.');
            }

            if ($terpakai->nilaiMinor() + $jumlah->nilaiMinor() < 0) {
                throw new AturanBisnisDilanggar('Penyesuaian tidak boleh membuat total realisasi menjadi negatif.');
            }

            if ($jumlah->nilaiMinor() > $sisa->nilaiMinor()) {
                throw new AturanBisnisDilanggar('Penyesuaian melebihi sisa anggaran yang tersedia.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buatTransaksi(PosAnggaran $pos, string $jenis, string $jumlah, array $data): TransaksiAnggaran
    {
        return TransaksiAnggaran::create([
            'OrganisasiId' => $pos->OrganisasiId,
            'PosAnggaranId' => $pos->Id,
            'Jenis' => $jenis,
            'ReferensiJenis' => $data['ReferensiJenis'] ?? null,
            'ReferensiId' => $data['ReferensiId'] ?? null,
            'Jumlah' => $jumlah,
            'Tanggal' => $data['Tanggal'],
            'Keterangan' => $data['Keterangan'] ?? null,
        ]);
    }
}
