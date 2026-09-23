<?php

declare(strict_types=1);

namespace App\Domain\Kodefikasi\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kodefikasi\Domain\Enums\StandarKodefikasi;
use App\Domain\Kodefikasi\Infrastructure\Persistence\Models\KodeBarang;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/**
 * Memuat daftar kodefikasi resmi dari berkas CSV.
 *
 * Idempotent per (standar, kode): mengimpor ulang memperbarui uraiannya, tidak
 * menggandakan barisnya. Baris yang kodenya tidak sesuai pola standar ditolak
 * dan dihitung, bukan diterima diam-diam -- kode yang salah bentuk akan lolos
 * ke laporan barang milik negara dan baru ketahuan di sana.
 */
final class ImporKatalogKodeBarang
{
    public const MAKS_BARIS = 50000;

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    /**
     * @param  resource  $pegangan
     * @return array{ditambah: int, diperbarui: int, dilewati: int, polaSalah: int}
     */
    public function jalankan($pegangan, StandarKodefikasi $standar): array
    {
        $kepala = fgetcsv($pegangan, escape: '');
        $hasil = ['ditambah' => 0, 'diperbarui' => 0, 'dilewati' => 0, 'polaSalah' => 0];

        if ($kepala === false) {
            return $hasil;
        }

        $peta = $this->petaKolom($kepala);
        $organisasiId = $this->konteks->wajibId();
        $dibaca = 0;

        while (($baris = fgetcsv($pegangan, escape: '')) !== false) {
            if (++$dibaca > self::MAKS_BARIS) {
                break;
            }

            $kode = $this->nilai($baris, $peta, 'Kode');
            $uraian = $this->nilai($baris, $peta, 'Uraian');

            if ($kode === '' || $uraian === '') {
                $hasil['dilewati']++;

                continue;
            }

            if (! $standar->cocok($kode)) {
                $hasil['polaSalah']++;

                continue;
            }

            $this->transaksi->jalankan(function () use ($organisasiId, $standar, $kode, $uraian, &$hasil): void {
                $ada = KodeBarang::query()
                    ->where('Standar', $standar->value)
                    ->where('Kode', $kode)
                    ->first();

                if ($ada === null) {
                    KodeBarang::create([
                        'OrganisasiId' => $organisasiId,
                        'Standar' => $standar->value,
                        'Kode' => $kode,
                        'Uraian' => $uraian,
                        'Aktif' => true,
                    ]);
                    $hasil['ditambah']++;

                    return;
                }

                $ada->update(['Uraian' => $uraian]);
                $hasil['diperbarui']++;
            });
        }

        return $hasil;
    }

    /**
     * @param  list<string|null>  $kepala
     * @return array<string, int>
     */
    private function petaKolom(array $kepala): array
    {
        $sinonim = [
            'Kode' => ['Kode', 'Kode Barang', 'KodeBarang', 'Kode Aset'],
            'Uraian' => ['Uraian', 'Nama', 'Nama Barang', 'Uraian Barang', 'NamaBarang'],
        ];

        $peta = [];

        foreach ($kepala as $indeks => $judul) {
            $bersih = trim((string) $judul);

            foreach ($sinonim as $ruas => $daftar) {
                if (isset($peta[$ruas])) {
                    continue;
                }

                foreach ($daftar as $calon) {
                    if (strcasecmp($bersih, $calon) === 0) {
                        $peta[$ruas] = $indeks;

                        break 2;
                    }
                }
            }
        }

        return $peta;
    }

    /**
     * @param  list<string|null>  $baris
     * @param  array<string, int>  $peta
     */
    private function nilai(array $baris, array $peta, string $ruas): string
    {
        $indeks = $peta[$ruas] ?? null;

        return $indeks === null ? '' : trim((string) ($baris[$indeks] ?? ''));
    }
}
