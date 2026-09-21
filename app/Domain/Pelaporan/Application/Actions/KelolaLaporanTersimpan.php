<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pelaporan\Application\Services\LayananMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\LaporanTersimpan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Filter laporan yang disimpan pengguna (21.03).
 *
 * Konfigurasi dinormalkan sebelum disimpan: kunci KPI yang tidak dikenal atau
 * tidak diizinkan bagi penyimpannya dibuang, dan rentang tanggal dibakukan.
 * Tanpa itu, laporan tersimpan menjadi jalur memutar untuk menyimpan kunci
 * sembarang yang baru meledak saat dibuka orang lain.
 */
final class KelolaLaporanTersimpan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananMetrik $metrik,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function simpan(Pengguna $pengguna, array $data, ?LaporanTersimpan $laporan = null): LaporanTersimpan
    {
        return $this->transaksi->jalankan(function () use ($pengguna, $data, $laporan): LaporanTersimpan {
            $kunciKpi = $this->metrik->saringYangDiizinkan(
                array_values(array_map('strval', (array) ($data['Konfigurasi']['KunciKpi'] ?? []))),
                $pengguna,
            );

            if ($kunciKpi === []) {
                throw new AturanBisnisDilanggar('Pilih minimal satu KPI yang boleh Anda lihat untuk laporan ini.');
            }

            $konfigurasi = [
                'KunciKpi' => $kunciKpi,
                'Filter' => FilterMetrik::dariArray((array) ($data['Konfigurasi']['Filter'] ?? []))->keArray(),
            ];

            $baru = $laporan === null;
            $laporan ??= new LaporanTersimpan;
            $laporan->fill([
                'Nama' => (string) $data['Nama'],
                'Jenis' => (string) ($data['Jenis'] ?? 'Kpi'),
                'Konfigurasi' => $konfigurasi,
                'Pribadi' => (bool) ($data['Pribadi'] ?? true),
            ]);

            // Kepemilikan ditetapkan sekali saat dibuat dan tidak berpindah
            // lewat pembaruan, supaya laporan tidak dapat "diambil alih".
            if ($baru) {
                $laporan->PemilikId = $pengguna->Id;
            }

            $laporan->save();

            $this->audit->catat(
                $baru ? 'LaporanTersimpan.Dibuat' : 'LaporanTersimpan.Diubah',
                'LaporanTersimpan',
                $laporan->Id,
                dataSesudah: ['Nama' => $laporan->Nama, 'Pribadi' => $laporan->Pribadi, 'KunciKpi' => $kunciKpi],
            );

            return $laporan->refresh();
        });
    }

    public function hapus(LaporanTersimpan $laporan): void
    {
        $id = $laporan->Id;
        $nama = $laporan->Nama;
        $laporan->delete();

        $this->audit->catat('LaporanTersimpan.Dihapus', 'LaporanTersimpan', $id, dataSebelum: ['Nama' => $nama]);
    }
}
