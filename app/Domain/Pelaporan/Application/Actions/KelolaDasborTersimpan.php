<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pelaporan\Application\Services\LayananDasbor;
use App\Domain\Pelaporan\Application\Services\LayananMetrik;
use App\Domain\Pelaporan\Domain\Enums\BentukKomponen;
use App\Domain\Pelaporan\Domain\KatalogKpi;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\DasborTersimpan;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\KomponenDasbor;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Dasbor kustom milik pengguna (21.04): komponen, urutan, lebar, dan penanda
 * dasbor bawaan.
 *
 * KPI tak dikenal, KPI di luar kewenangan penyimpan, dan bentuk yang tidak
 * mungkin digambar ditolak di server, bukan disembunyikan di klien.
 */
final class KelolaDasborTersimpan
{
    /**
     * Batas komponen per dasbor supaya satu halaman tidak menjadi ratusan
     * query.
     */
    public const BATAS_KOMPONEN = 24;

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananMetrik $metrik,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function simpan(Pengguna $pengguna, array $data, ?DasborTersimpan $dasbor = null): DasborTersimpan
    {
        return $this->transaksi->jalankan(function () use ($pengguna, $data, $dasbor): DasborTersimpan {
            $komponen = $this->normalkanKomponen((array) ($data['Komponen'] ?? []), $pengguna);

            if ($komponen === []) {
                throw new AturanBisnisDilanggar('Dasbor harus memuat minimal satu komponen yang boleh Anda lihat.');
            }

            $baru = $dasbor === null;
            $dasbor ??= new DasborTersimpan;
            $dasbor->fill([
                'Nama' => (string) $data['Nama'],
                'Konfigurasi' => null,
            ]);
            if ($baru) {
                $dasbor->PemilikId = $pengguna->Id;
            }
            $dasbor->Bawaan = (bool) ($data['Bawaan'] ?? false);
            $dasbor->save();

            if ($dasbor->Bawaan) {
                $this->jadikanSatuSatunyaBawaan($dasbor, $pengguna);
            }

            // Komponen ditulis ulang seluruhnya: susunan adalah satu kesatuan,
            // dan pembaruan sebagian akan meninggalkan urutan yang berlubang.
            $dasbor->komponen()->delete();
            foreach ($komponen as $urutan => $satu) {
                KomponenDasbor::create([
                    'DasborTersimpanId' => $dasbor->Id,
                    'JenisKomponen' => 'Kpi',
                    'Judul' => $satu['Judul'],
                    'Konfigurasi' => ['KunciKpi' => $satu['KunciKpi'], 'Bentuk' => $satu['Bentuk']],
                    'Lebar' => $satu['Lebar'],
                    'Urutan' => $urutan,
                ]);
            }

            $this->audit->catat(
                $baru ? 'DasborTersimpan.Dibuat' : 'DasborTersimpan.Diubah',
                'DasborTersimpan',
                $dasbor->Id,
                dataSesudah: ['Nama' => $dasbor->Nama, 'JumlahKomponen' => count($komponen)],
            );

            return $dasbor->load('komponen');
        });
    }

    public function hapus(DasborTersimpan $dasbor): void
    {
        $id = $dasbor->Id;
        $nama = $dasbor->Nama;

        $this->transaksi->jalankan(function () use ($dasbor): void {
            $dasbor->komponen()->delete();
            $dasbor->delete();
        });

        $this->audit->catat('DasborTersimpan.Dihapus', 'DasborTersimpan', $id, dataSebelum: ['Nama' => $nama]);
    }

    /**
     * @param  array<int, mixed>  $komponen
     * @return list<array{KunciKpi: string, Bentuk: string, Judul: string|null, Lebar: int}>
     */
    private function normalkanKomponen(array $komponen, Pengguna $pengguna): array
    {
        if (count($komponen) > self::BATAS_KOMPONEN) {
            throw new AturanBisnisDilanggar('Satu dasbor maksimal memuat '.self::BATAS_KOMPONEN.' komponen.');
        }

        $hasil = [];

        foreach ($komponen as $satu) {
            if (! is_array($satu)) {
                continue;
            }

            $kunciKpi = (string) ($satu['KunciKpi'] ?? '');
            if (! KatalogKpi::ada($kunciKpi)) {
                throw new AturanBisnisDilanggar("KPI {$kunciKpi} tidak dikenal.");
            }

            $definisi = KatalogKpi::ambil($kunciKpi);
            if (! $this->metrik->boleh($definisi, $pengguna)) {
                throw new AturanBisnisDilanggar("Anda tidak berhak menampilkan KPI {$definisi->nama}.");
            }

            $bentuk = BentukKomponen::tryFrom((string) ($satu['Bentuk'] ?? ''))
                ?? throw new AturanBisnisDilanggar('Bentuk komponen tidak dikenal.');

            if (! in_array($bentuk, BentukKomponen::untukKpi($definisi), true)) {
                throw new AturanBisnisDilanggar(
                    "KPI {$definisi->nama} tidak dapat ditampilkan sebagai {$bentuk->label()}.",
                );
            }

            $lebar = (int) ($satu['Lebar'] ?? 1);
            $lebar = max(LayananDasbor::LEBAR_MIN, min(LayananDasbor::LEBAR_MAKS, $lebar));

            $judul = isset($satu['Judul']) && trim((string) $satu['Judul']) !== ''
                ? mb_substr(trim((string) $satu['Judul']), 0, 180)
                : null;

            $hasil[] = [
                'KunciKpi' => $kunciKpi,
                'Bentuk' => $bentuk->value,
                'Judul' => $judul,
                'Lebar' => $lebar,
            ];
        }

        return $hasil;
    }

    /**
     * Preferensi dasbor bawaan bersifat per pengguna, jadi penandaan hanya
     * melepas penanda pada dasbor milik pengguna yang sama.
     */
    private function jadikanSatuSatunyaBawaan(DasborTersimpan $dasbor, Pengguna $pengguna): void
    {
        DasborTersimpan::query()
            ->where('PemilikId', $pengguna->Id)
            ->whereKeyNot($dasbor->Id)
            ->update(['Bawaan' => false]);
    }
}
