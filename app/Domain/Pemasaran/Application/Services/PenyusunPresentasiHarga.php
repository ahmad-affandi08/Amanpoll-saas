<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Domain\ValueObjects\DefinisiFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\Enums\SiklusHarga;

/**
 * Menyusun presentasi harga dari domain Langganan (MARKETING.md 19).
 *
 * Domain Pemasaran hanya menyimpan kode paket beserta urutan, sorotan, badge,
 * dan CTA-nya. Angkanya tidak pernah disalin ke sini, dan penyusunannya berjalan
 * di luar cache isi halaman supaya harga yang tampil selalu harga paket hari ini.
 */
final class PenyusunPresentasiHarga
{
    /**
     * Menerapkan presentasi harga pada isi halaman yang sudah tersusun.
     *
     * @param  array<string, mixed>  $isi
     * @return array<string, mixed>
     */
    public function terapkan(array $isi): array
    {
        $blok = $isi['Blok'] ?? null;

        if (! is_array($blok)) {
            return $isi;
        }

        $isi['Blok'] = array_map($this->terapkanBlok(...), array_values($blok));

        return $isi;
    }

    /**
     * @return array<string, mixed>
     */
    private function terapkanBlok(mixed $blok): array
    {
        if (! is_array($blok)) {
            return [];
        }

        $jenis = is_string($blok['Jenis'] ?? null) ? $blok['Jenis'] : '';
        $isiBlok = is_array($blok['Isi'] ?? null) ? $blok['Isi'] : [];

        if ($jenis === JenisBlokHalaman::Harga->value) {
            $blok['Isi'] = [...$isiBlok, 'Paket' => $this->paket($isiBlok)];
        }

        if ($jenis === JenisBlokHalaman::Perbandingan->value) {
            $blok['Isi'] = [...$isiBlok, ...$this->perbandingan($isiBlok)];
        }

        return $blok;
    }

    /**
     * Kartu paket yang benar-benar tampil, lengkap dengan harga hari ini.
     *
     * @param  array<string, mixed>  $isi
     * @return list<array<string, mixed>>
     */
    public function paket(array $isi): array
    {
        $pilihan = $this->daftarObjek($isi, 'paket');

        if ($pilihan === []) {
            return [];
        }

        $siklus = SiklusHarga::tryFrom($this->teks($isi, 'siklus')) ?? SiklusHarga::Bulanan;
        $paket = $this->paketAktif($this->kodeDari($pilihan));
        $fitur = $this->fiturPerPaket(array_values(array_map(
            fn (PaketLangganan $satu): string => $satu->Id,
            $paket,
        )));

        $kartu = [];

        foreach ($pilihan as $satu) {
            $kode = $this->teks($satu, 'kode');
            $baris = $paket[$kode] ?? null;

            // Paket yang tidak ada atau sudah dinonaktifkan tidak boleh tampil sebagai kartu kosong.
            if ($baris === null) {
                continue;
            }

            $kartu[] = [
                'Kode' => $baris->Kode,
                'Nama' => $baris->Nama,
                'Deskripsi' => $baris->Deskripsi,
                'Harga' => $siklus->hargaDari($baris),
                'MataUang' => $baris->MataUang,
                'Siklus' => $siklus->value,
                'LabelSiklus' => $siklus->label(),
                'Fitur' => $fitur[$baris->Id] ?? [],
                'Disorot' => (bool) ($satu['disorot'] ?? false),
                'Badge' => $this->teksOpsional($satu, 'badge'),
                'Ringkasan' => $this->teksOpsional($satu, 'ringkasan'),
                'CtaTeks' => $this->teksOpsional($satu, 'ctaTeks'),
                'CtaUrl' => $this->teksOpsional($satu, 'ctaUrl'),
            ];
        }

        return $kartu;
    }

    /**
     * Tabel perbandingan yang disusun dari PaketFitur, bukan diketik ulang.
     * Blok yang tidak menyebut paket apa pun dibiarkan memakai isinya sendiri.
     *
     * @param  array<string, mixed>  $isi
     * @return array<string, mixed>
     */
    public function perbandingan(array $isi): array
    {
        $kodePaket = $this->daftarTeks($isi, 'paket');

        if ($kodePaket === []) {
            return [];
        }

        $paket = $this->paketAktif($kodePaket);
        $urut = array_values(array_filter(
            $kodePaket,
            fn (string $kode): bool => isset($paket[$kode]),
        ));

        if ($urut === []) {
            return ['kolom' => [], 'baris' => []];
        }

        $kodeFitur = $this->daftarTeks($isi, 'fitur');
        $kodeFitur = $kodeFitur === [] ? KatalogFitur::kode() : $kodeFitur;
        $nilai = $this->nilaiFitur(array_map(fn (string $kode): string => $paket[$kode]->Id, $urut));

        $baris = [];

        foreach ($kodeFitur as $kode) {
            if (! KatalogFitur::ada($kode)) {
                continue;
            }

            $definisi = KatalogFitur::ambil($kode);

            $baris[] = [
                'label' => $definisi->nama,
                'nilai' => array_map(
                    fn (string $kodePaketSatu): string => $this->tampilkan(
                        $definisi,
                        $nilai[$paket[$kodePaketSatu]->Id][$kode] ?? null,
                    ),
                    $urut,
                ),
            ];
        }

        return [
            'kolom' => array_map(fn (string $kode): string => $paket[$kode]->Nama, $urut),
            'baris' => $baris,
        ];
    }

    /** @return list<array{Kode: string, Nama: string}> */
    public function paketTersedia(): array
    {
        return array_values(PaketLangganan::query()
            ->where('Aktif', true)
            ->orderBy('HargaBulanan')
            ->get(['Kode', 'Nama'])
            ->map(fn (PaketLangganan $satu): array => ['Kode' => $satu->Kode, 'Nama' => $satu->Nama])
            ->all());
    }

    public function paketDikenal(string $kode): bool
    {
        return PaketLangganan::query()->where('Kode', $kode)->exists();
    }

    /**
     * @param  list<string>  $kode
     * @return array<string, PaketLangganan>
     */
    private function paketAktif(array $kode): array
    {
        if ($kode === []) {
            return [];
        }

        /** @var array<string, PaketLangganan> $paket */
        $paket = PaketLangganan::query()
            ->whereIn('Kode', $kode)
            ->where('Aktif', true)
            ->get()
            ->keyBy('Kode')
            ->all();

        return $paket;
    }

    /**
     * Fitur yang diizinkan tiap paket, dibaca sekali untuk seluruh paket.
     *
     * @param  list<string>  $paketId
     * @return array<string, list<string>>
     */
    private function fiturPerPaket(array $paketId): array
    {
        $hasil = [];

        foreach ($this->barisFitur($paketId) as $baris) {
            if (! $baris->Diizinkan || ! KatalogFitur::ada((string) $baris->fiturPaket?->Kode)) {
                continue;
            }

            $definisi = KatalogFitur::ambil((string) $baris->fiturPaket?->Kode);
            $hasil[$baris->PaketLanggananId][] = $this->tampilkan($definisi, $baris);
        }

        return $hasil;
    }

    /**
     * @param  list<string>  $paketId
     * @return array<string, array<string, PaketFitur>>
     */
    private function nilaiFitur(array $paketId): array
    {
        $hasil = [];

        foreach ($this->barisFitur($paketId) as $baris) {
            $kode = (string) $baris->fiturPaket?->Kode;

            if ($kode !== '') {
                $hasil[$baris->PaketLanggananId][$kode] = $baris;
            }
        }

        return $hasil;
    }

    /**
     * @param  list<string>  $paketId
     * @return list<PaketFitur>
     */
    private function barisFitur(array $paketId): array
    {
        if ($paketId === []) {
            return [];
        }

        /** @var list<PaketFitur> $baris */
        $baris = PaketFitur::query()
            ->with('fiturPaket:Id,Kode')
            ->whereIn('PaketLanggananId', $paketId)
            ->get()
            ->all();

        return $baris;
    }

    /** Fitur berbatas angka tampil sebagai angka beserta satuannya, bukan sebagai centang. */
    private function tampilkan(DefinisiFitur $definisi, ?PaketFitur $baris): string
    {
        if ($baris === null || ! $baris->Diizinkan) {
            return '—';
        }

        if ($baris->BatasNilai === null) {
            return $definisi->nama;
        }

        $angka = number_format((float) $baris->BatasNilai, 0, ',', '.');

        return $definisi->satuanBatas === null ? $angka : $angka.' '.$definisi->satuanBatas;
    }

    /**
     * @param  list<array<string, mixed>>  $pilihan
     * @return list<string>
     */
    private function kodeDari(array $pilihan): array
    {
        return array_values(array_filter(array_map(
            fn (array $satu): string => $this->teks($satu, 'kode'),
            $pilihan,
        )));
    }

    /**
     * @param  array<string, mixed>  $isi
     * @return list<array<string, mixed>>
     */
    private function daftarObjek(array $isi, string $kunci): array
    {
        $nilai = $isi[$kunci] ?? null;

        if (! is_array($nilai)) {
            return [];
        }

        return array_values(array_filter($nilai, is_array(...)));
    }

    /**
     * @param  array<string, mixed>  $isi
     * @return list<string>
     */
    private function daftarTeks(array $isi, string $kunci): array
    {
        $nilai = $isi[$kunci] ?? null;

        if (! is_array($nilai)) {
            return [];
        }

        return array_values(array_filter($nilai, is_string(...)));
    }

    /** @param array<string, mixed> $isi */
    private function teks(array $isi, string $kunci): string
    {
        $nilai = $isi[$kunci] ?? null;

        return is_string($nilai) ? trim($nilai) : '';
    }

    /** @param array<string, mixed> $isi */
    private function teksOpsional(array $isi, string $kunci): ?string
    {
        $nilai = $this->teks($isi, $kunci);

        return $nilai === '' ? null : $nilai;
    }
}
