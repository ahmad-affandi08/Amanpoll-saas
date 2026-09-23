<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aspak\Infrastructure\Persistence\Models\AlkesAspak;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/**
 * Memuat katalog nomenklatur ASPAK dari berkas CSV.
 *
 * Idempotent per kode: mengimpor ulang berkas yang sama memperbarui nama dan
 * kelompoknya, tidak menggandakan barisnya. Itu penting karena rumah sakit
 * mengunduh katalog terbaru setiap kali ASPAK memutakhirkan nomenklaturnya.
 */
final class ImporKatalogAspak
{
    /** Berkas katalog yang wajar berisi ribuan baris, bukan ratusan ribu. */
    public const MAKS_BARIS = 20000;

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    /**
     * @param  resource  $pegangan
     * @return array{ditambah: int, diperbarui: int, dilewati: int}
     */
    public function jalankan($pegangan): array
    {
        $kepala = fgetcsv($pegangan, escape: '');

        if ($kepala === false) {
            return ['ditambah' => 0, 'diperbarui' => 0, 'dilewati' => 0];
        }

        $peta = $this->petaKolom($kepala);
        $hasil = ['ditambah' => 0, 'diperbarui' => 0, 'dilewati' => 0];
        $dibaca = 0;

        $organisasiId = $this->konteks->wajibId();

        while (($baris = fgetcsv($pegangan, escape: '')) !== false) {
            if (++$dibaca > self::MAKS_BARIS) {
                break;
            }

            $kode = $this->nilai($baris, $peta, 'Kode');
            $nama = $this->nilai($baris, $peta, 'Nama');

            // Kode adalah kuncinya di ASPAK dan nama adalah isinya; tanpa
            // salah satu, barisnya tidak dapat dipakai untuk apa pun.
            if ($kode === '' || $nama === '') {
                $hasil['dilewati']++;

                continue;
            }

            $this->transaksi->jalankan(function () use ($organisasiId, $kode, $nama, $baris, $peta, &$hasil): void {
                $alkes = AlkesAspak::query()->where('Kode', $kode)->first();

                $atribut = [
                    'Nama' => $nama,
                    'Kelompok' => $this->nilai($baris, $peta, 'Kelompok') ?: null,
                    'Satuan' => $this->nilai($baris, $peta, 'Satuan') ?: null,
                ];

                if ($alkes === null) {
                    AlkesAspak::create([
                        'OrganisasiId' => $organisasiId,
                        'Kode' => $kode,
                        'Aktif' => true,
                        ...$atribut,
                    ]);
                    $hasil['ditambah']++;

                    return;
                }

                $alkes->update($atribut);
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
        // Judul kolom katalog ASPAK tidak seragam antar unduhan, jadi beberapa
        // ejaan yang lazim diterima sekaligus.
        $sinonim = [
            'Kode' => ['Kode', 'Kode Alat', 'KodeAlat', 'Kode Alkes', 'Kode Barang'],
            'Nama' => ['Nama', 'Nama Alat', 'NamaAlat', 'Nama Alkes', 'Nama Barang'],
            'Kelompok' => ['Kelompok', 'Kategori', 'Jenis', 'Kelompok Alat'],
            'Satuan' => ['Satuan', 'Satuan Alat', 'Unit'],
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
