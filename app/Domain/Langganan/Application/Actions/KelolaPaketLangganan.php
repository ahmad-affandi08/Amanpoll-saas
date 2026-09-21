<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Domain\Enums\TipeBatasFitur;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\FiturPaket;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * CRUD paket langganan beserta entitlement-nya (22.02/22.03).
 *
 * Paket dan isinya disimpan dalam satu transaksi karena paket tanpa baris fitur
 * bukan paket setengah jadi melainkan paket yang salah: ia akan jatuh ke nilai
 * bawaan katalog dan diam-diam menutup modul yang sudah dijual.
 */
final class KelolaPaketLangganan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly PemeriksaEntitlement $entitlement,
    ) {}

    /** @param array<string, mixed> $data */
    public function simpan(array $data, ?PaketLangganan $paket = null): PaketLangganan
    {
        return $this->transaksi->jalankan(function () use ($data, $paket): PaketLangganan {
            $fitur = $this->normalkanFitur((array) ($data['Fitur'] ?? []));

            $baru = $paket === null;
            $paket ??= new PaketLangganan;
            $paket->fill([
                'Kode' => (string) $data['Kode'],
                'Nama' => (string) $data['Nama'],
                'Deskripsi' => $data['Deskripsi'] ?? null,
                'HargaBulanan' => (float) ($data['HargaBulanan'] ?? 0),
                'HargaTahunan' => (float) ($data['HargaTahunan'] ?? 0),
                'MataUang' => (string) ($data['MataUang'] ?? config('amanpoll.mata_uang', 'IDR')),
                'Aktif' => (bool) ($data['Aktif'] ?? true),
            ]);
            $paket->save();

            $this->tulisUlangFitur($paket, $fitur);

            $this->audit->catat(
                $baru ? 'PaketLangganan.Dibuat' : 'PaketLangganan.Diubah',
                'PaketLangganan',
                $paket->Id,
                dataSesudah: ['Kode' => $paket->Kode, 'Nama' => $paket->Nama, 'JumlahFitur' => count($fitur)],
            );

            // Perubahan isi paket langsung terasa bagi pelanggannya; tanpa ini,
            // modul yang baru dicabut masih dapat dipakai sampai lima menit.
            $this->entitlement->bersihkanCachePaket((string) $paket->Id);

            return $paket->load('fitur');
        });
    }

    public function hapus(PaketLangganan $paket): void
    {
        if ($paket->langganan()->withoutGlobalScopes()->exists()) {
            throw new AturanBisnisDilanggar(
                'Paket ini masih dipakai oleh langganan yang berjalan, jadi tidak dapat dihapus. '
                .'Nonaktifkan paket agar tidak dapat dipilih lagi oleh langganan baru.',
            );
        }

        $id = $paket->Id;
        $kode = $paket->Kode;

        $this->transaksi->jalankan(function () use ($paket): void {
            $paket->fitur()->delete();
            $paket->delete();
        });

        $this->audit->catat('PaketLangganan.Dihapus', 'PaketLangganan', $id, dataSebelum: ['Kode' => $kode]);
    }

    /**
     * @param  array<int, mixed>  $fitur
     * @return list<array{Kode: string, Diizinkan: bool, BatasNilai: float|null}>
     */
    private function normalkanFitur(array $fitur): array
    {
        $hasil = [];
        $sudah = [];

        foreach ($fitur as $satu) {
            if (! is_array($satu)) {
                continue;
            }

            $kode = (string) ($satu['Kode'] ?? '');
            if (! KatalogFitur::ada($kode)) {
                throw new AturanBisnisDilanggar("Fitur {$kode} tidak dikenal.");
            }
            if (isset($sudah[$kode])) {
                throw new AturanBisnisDilanggar("Fitur {$kode} disebut dua kali pada paket yang sama.");
            }
            $sudah[$kode] = true;

            $definisi = KatalogFitur::ambil($kode);
            $diizinkan = (bool) ($satu['Diizinkan'] ?? false);
            $batas = $this->batasUntuk($satu, $definisi->tipeBatas, $kode);

            $hasil[] = ['Kode' => $kode, 'Diizinkan' => $diizinkan, 'BatasNilai' => $batas];
        }

        return $hasil;
    }

    /**
     * Batas hanya bermakna untuk fitur bertipe Angka. Menolak batas pada fitur
     * Boolean mencegah paket yang tampak membatasi padahal batasnya tidak
     * pernah dibaca siapa pun.
     *
     * @param  array<string, mixed>  $satu
     */
    private function batasUntuk(array $satu, TipeBatasFitur $tipe, string $kode): ?float
    {
        $mentah = $satu['BatasNilai'] ?? null;

        if ($tipe === TipeBatasFitur::Boolean) {
            if ($mentah !== null && $mentah !== '') {
                throw new AturanBisnisDilanggar("Fitur {$kode} bertipe aktif/nonaktif, jadi tidak menerima batas jumlah.");
            }

            return null;
        }

        if ($mentah === null || $mentah === '') {
            // Tanpa batas. Dibedakan dari nol, yang berarti tidak boleh sama sekali.
            return null;
        }

        $batas = (float) $mentah;
        if ($batas < 0) {
            throw new AturanBisnisDilanggar("Batas fitur {$kode} tidak boleh negatif.");
        }

        return $batas;
    }

    /** @param list<array{Kode: string, Diizinkan: bool, BatasNilai: float|null}> $fitur */
    private function tulisUlangFitur(PaketLangganan $paket, array $fitur): void
    {
        $paket->fitur()->delete();

        foreach ($fitur as $satu) {
            $master = FiturPaket::query()->where('Kode', $satu['Kode'])->first();
            if ($master === null) {
                throw new AturanBisnisDilanggar(
                    "Master fitur {$satu['Kode']} belum disemai. Jalankan seeder fitur paket lebih dulu.",
                );
            }

            PaketFitur::create([
                'PaketLanggananId' => $paket->Id,
                'FiturPaketId' => $master->Id,
                'Diizinkan' => $satu['Diizinkan'],
                'BatasNilai' => $satu['BatasNilai'],
            ]);
        }
    }
}
