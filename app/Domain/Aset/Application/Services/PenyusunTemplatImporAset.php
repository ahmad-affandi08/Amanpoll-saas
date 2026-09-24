<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Services;

use App\Domain\Aset\Domain\ValueObjects\KolomImporAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Platform\Application\Services\OpsiUnitPengelola;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Infrastructure\Ekspor\NetralkanRumus;
use App\Shared\Infrastructure\Ekspor\PenulisEksporCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Menulis templat impor aset (CSV dan XLSX) dan berkas daftar galatnya.
 *
 * Templat berisi judul kolom dan satu baris contoh yang memakai kode milik
 * organisasi ini, jadi baris contohnya sendiri lolos pemeriksaan. XLSX
 * mendapat lembar kedua "Petunjuk" berisi arti kolom dan daftar kode yang
 * sah; lembar itu diabaikan saat berkas diunggah kembali karena yang dibaca
 * hanya lembar pertama.
 *
 * Setiap nilai -- termasuk kode dan nama milik tenant di lembar petunjuk --
 * melewati NetralkanRumus, sama seperti seluruh ekspor lain.
 */
final class PenyusunTemplatImporAset
{
    /** Batas butir per daftar kode di lembar petunjuk, supaya lembarnya tetap terbaca. */
    public const MAKS_KODE_PER_DAFTAR = 200;

    public function csv(string $jalur): void
    {
        [$kepala, $contoh] = $this->kepalaDanContoh();

        (new PenulisEksporCsv)->tulis($jalur, $kepala, [$contoh], []);
    }

    public function xlsx(string $jalur): void
    {
        [$kepala, $contoh] = $this->kepalaDanContoh();
        $tebal = (new Style)->withFontBold(true);

        $penulis = new Writer;
        $penulis->openToFile($jalur);

        try {
            $penulis->getCurrentSheet()->setName('Aset');
            $penulis->addRow(Row::fromValuesWithStyle(NetralkanRumus::barisXlsx($kepala), $tebal));
            $penulis->addRow(Row::fromValues(NetralkanRumus::barisXlsx($contoh)));

            $penulis->addNewSheetAndMakeItCurrent()->setName('Petunjuk');

            foreach ($this->barisPetunjuk() as [$baris, $judul]) {
                $penulis->addRow($judul
                    ? Row::fromValuesWithStyle(NetralkanRumus::barisXlsx($baris), $tebal)
                    : Row::fromValues(NetralkanRumus::barisXlsx($baris)));
            }
        } finally {
            $penulis->close();
        }
    }

    /**
     * @param  list<array{baris: int, kolom: string, nilai: string, pesan: string}>  $galat
     */
    public function galatCsv(string $jalur, array $galat): void
    {
        (new PenulisEksporCsv)->tulis(
            $jalur,
            ['Baris', 'Kolom', 'Isi Sel', 'Pesan'],
            array_map(static fn (array $satu): array => [$satu['baris'], $satu['kolom'], $satu['nilai'], $satu['pesan']], $galat),
            [],
        );
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function kepalaDanContoh(): array
    {
        $kolom = KolomImporAset::untukTemplat(OpsiUnitPengelola::dipakai());

        // Kode milik organisasi ini supaya baris contohnya sendiri sah; lokasi
        // dibaca lewat ScopeLingkup sehingga pengguna berlingkup mendapat
        // contoh lokasi yang memang boleh ia pakai.
        $nilaiContoh = [
            'Nama' => 'Contoh: Tensimeter Digital (hapus baris ini)',
            'KategoriAsetId' => (string) KategoriAset::query()->orderBy('Nama')->orderBy('Id')->value('Kode'),
            'UnitPengelolaId' => OpsiUnitPengelola::daftar()[0]['Kode'] ?? '',
            'LokasiId' => (string) Lokasi::query()->orderBy('Nama')->orderBy('Id')->value('Kode'),
            'NomorSeri' => 'SN-12345',
            'TanggalPerolehan' => '2024-05-31',
            'HargaPerolehan' => '15000000',
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
            'TingkatKritis' => 'Normal',
        ];

        return [
            array_map(static fn (array $satu): string => $satu['judul'], $kolom),
            array_map(static fn (array $satu): string => $nilaiContoh[$satu['ruas']] ?? '', $kolom),
        ];
    }

    /**
     * @return list<array{0: list<string>, 1: bool}> baris dan apakah ia judul
     */
    private function barisPetunjuk(): array
    {
        $unitPengelolaDipakai = OpsiUnitPengelola::dipakai();

        $baris = [
            [['Petunjuk impor aset'], true],
            [['Isi lembar "Aset", satu aset per baris. Hapus baris contoh sebelum mengunggah.'], false],
            [['Paling banyak 1.000 baris dan 5 MB per berkas. Impor hanya menambah aset baru; bila satu baris salah, tidak ada yang dibuat.'], false],
            [['Rujukan diisi dengan kode, bukan nama. Urutan kolom bebas, tetapi judulnya harus sama dengan templat.'], false],
            [[], false],
            [['Kolom', 'Keterangan'], true],
        ];

        foreach (KolomImporAset::untukTemplat($unitPengelolaDipakai) as $kolom) {
            $baris[] = [[$kolom['judul'], $kolom['keterangan']], false];
        }

        $daftar = [
            'Kode Kategori' => KategoriAset::query()->orderBy('Nama')->orderBy('Id'),
            'Kode Lokasi' => Lokasi::query()->orderBy('Nama')->orderBy('Id'),
            'Kode Unit Organisasi' => UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->orderBy('Id'),
        ];

        foreach ($daftar as $judul => $kueri) {
            $baris = [...$baris, ...$this->daftarKode($judul, $kueri)];
        }

        if ($unitPengelolaDipakai) {
            $unit = OpsiUnitPengelola::daftar();
            $baris[] = [[], false];
            $baris[] = [['Kode Unit Pengelola', 'Nama'], true];

            foreach (array_slice($unit, 0, self::MAKS_KODE_PER_DAFTAR) as $satu) {
                $baris[] = [[$satu['Kode'], $satu['Nama']], false];
            }
        }

        return $baris;
    }

    /**
     * @param  Builder<covariant Model>  $kueri
     * @return list<array{0: list<string>, 1: bool}>
     */
    private function daftarKode(string $judul, Builder $kueri): array
    {
        $jumlah = (clone $kueri)->count();
        $baris = [[[], false], [[$judul, 'Nama'], true]];

        foreach ($kueri->limit(self::MAKS_KODE_PER_DAFTAR)->get(['Kode', 'Nama']) as $satu) {
            $baris[] = [[(string) $satu->getAttribute('Kode'), (string) $satu->getAttribute('Nama')], false];
        }

        if ($jumlah === 0) {
            $baris[] = [['(belum ada)'], false];
        } elseif ($jumlah > self::MAKS_KODE_PER_DAFTAR) {
            $baris[] = [[sprintf('... dan %d lainnya; lihat halaman masternya untuk daftar lengkap.', $jumlah - self::MAKS_KODE_PER_DAFTAR)], false];
        }

        return $baris;
    }
}
