<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\LayananKontrak;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class KelolaLayananKontrak
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function tambah(Kontrak $kontrak, array $data): LayananKontrak
    {
        if ($kontrak->Status !== Kontrak::STATUS_AKTIF) {
            throw new AturanBisnisDilanggar('Layanan hanya dapat ditambahkan pada kontrak berstatus aktif.');
        }

        $layanan = LayananKontrak::create([
            'OrganisasiId' => $kontrak->OrganisasiId,
            'KontrakId' => $kontrak->Id,
            'Nama' => $data['Nama'],
            'Deskripsi' => $data['Deskripsi'] ?? null,
            'Kuota' => $data['Kuota'] ?? null,
            'Satuan' => $data['Satuan'] ?? null,
            'Terpakai' => '0',
        ]);
        $this->audit->catat('LayananKontrak.Ditambahkan', 'Kontrak', $kontrak->Id, dataSesudah: $layanan->toArray());

        return $layanan;
    }

    public function hapus(Kontrak $kontrak, LayananKontrak $layanan): void
    {
        if ($layanan->KontrakId !== $kontrak->Id) {
            throw new AturanBisnisDilanggar('Layanan bukan bagian dari kontrak ini.');
        }
        if ($this->keInteger((string) $layanan->Terpakai) > 0) {
            throw new AturanBisnisDilanggar('Layanan yang sudah terpakai tidak dapat dihapus.');
        }

        $sebelum = $layanan->toArray();
        $layanan->delete();
        $this->audit->catat('LayananKontrak.Dihapus', 'Kontrak', $kontrak->Id, dataSebelum: $sebelum);
    }

    /**
     * Mencatat pemakaian kuota layanan. Pemakaian dikunci di dalam transaksi agar
     * dua pencatatan bersamaan tidak melampaui kuota kontrak.
     */
    public function catatPemakaian(LayananKontrak $layanan, string $jumlah): LayananKontrak
    {
        if ($this->keInteger($jumlah) <= 0) {
            throw new AturanBisnisDilanggar('Jumlah pemakaian layanan harus lebih besar dari nol.');
        }

        return $this->transaksi->jalankan(function () use ($layanan, $jumlah): LayananKontrak {
            $terkunci = LayananKontrak::query()->whereKey($layanan->Id)->lockForUpdate()->firstOrFail();
            $terpakaiBaru = $this->keInteger((string) $terkunci->Terpakai) + $this->keInteger($jumlah);

            if ($terkunci->Kuota !== null && $terpakaiBaru > $this->keInteger((string) $terkunci->Kuota)) {
                throw new AturanBisnisDilanggar("Pemakaian melampaui kuota layanan {$terkunci->Nama}.");
            }

            $terkunci->Terpakai = $this->keDesimal($terpakaiBaru);
            $terkunci->save();
            $this->audit->catat('LayananKontrak.PemakaianDicatat', 'Kontrak', $terkunci->KontrakId, dataSesudah: [
                'LayananKontrakId' => $terkunci->Id,
                'Terpakai' => $terkunci->Terpakai,
            ]);

            return $terkunci->refresh();
        });
    }

    /** Kuota memakai skala 4 desimal; hitung sebagai integer supaya bebas galat float. */
    private function keInteger(string $nilai): int
    {
        [$bulat, $pecahan] = array_pad(explode('.', trim($nilai), 2), 2, '');

        return ((int) $bulat * 10000) + (int) substr(str_pad($pecahan, 4, '0'), 0, 4);
    }

    private function keDesimal(int $nilai): string
    {
        return sprintf('%d.%04d', intdiv($nilai, 10000), $nilai % 10000);
    }
}
