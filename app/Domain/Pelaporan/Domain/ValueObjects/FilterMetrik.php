<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\ValueObjects;

use Carbon\CarbonImmutable;

/**
 * Irisan data yang berlaku untuk seluruh KPI pada satu tampilan (21.02).
 *
 * Rentang dipilih sebagai tanggal kalender rumah sakit, tetapi waktu disimpan
 * UTC. Karena itu `dari` dan `sampai` adalah MOMEN UTC tempat hari pertama
 * dimulai dan hari terakhir berakhir di zona organisasi, siap dipakai untuk
 * kolom waktu berjam. Kolom `date` memakai `tanggalDari()`/`tanggalSampai()`:
 * `dari->toDateString()` memberi tanggal UTC, yang untuk Jakarta adalah hari
 * sebelumnya.
 *
 * Unit pengelola (PRD 8.21) adalah dimensi ketiga di samping unit organisasi
 * dan lokasi: "siapa yang memelihara", bukan "milik siapa". Kueri metrik
 * memakai kolom unit pengelola barisnya sendiri bila ada (Keluhan,
 * PerintahKerja, Aset, Gudang untuk stok) dan tidak pernah menukarnya dengan
 * filter unit organisasi.
 */
final readonly class FilterMetrik
{
    /**
     * @param  list<string>  $unitOrganisasiId
     * @param  list<string>  $lokasiId
     * @param  list<string>  $unitPengelolaId  ditaruh paling akhir supaya pemanggil posisional lama tidak bergeser
     */
    public function __construct(
        public CarbonImmutable $dari,
        public CarbonImmutable $sampai,
        public array $unitOrganisasiId = [],
        public array $lokasiId = [],
        public string $zona = 'UTC',
        public array $unitPengelolaId = [],
    ) {}

    /** Rentang bawaan dasbor: 30 hari terakhir sampai akhir hari ini, di zona organisasi. */
    public static function bawaan(string $zona): self
    {
        return self::dariArray([], $zona);
    }

    /** @param array<string, mixed> $data */
    public static function dariArray(array $data, string $zona): self
    {
        $hariIni = CarbonImmutable::now($zona)->startOfDay();

        // Tanggal yang tidak dapat dibaca kembali ke bawaannya, bukan menggagalkan halaman.
        $dari = self::tanggalMasukan($data['Dari'] ?? null, $zona) ?? $hariIni->subDays(29);
        $sampai = self::tanggalMasukan($data['Sampai'] ?? null, $zona) ?? $hariIni;

        // Rentang terbalik dinormalkan daripada menghasilkan laporan kosong yang membingungkan.
        if ($sampai->lessThan($dari)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return new self(
            $dari->startOfDay()->utc(),
            $sampai->endOfDay()->utc(),
            self::daftarId($data['UnitOrganisasiId'] ?? []),
            self::daftarId($data['LokasiId'] ?? []),
            $zona,
            self::daftarId($data['UnitPengelolaId'] ?? []),
        );
    }

    /**
     * Tanggal kalender dari masukan (`2026-09-24`, atau awal teks ISO seperti
     * `2026-09-24T00:00`), atau null bila bukan tanggal yang sah.
     *
     * Dibaca ketat: `abc`, `2026-02-31`, larik, dan tahun di luar 1900–2999
     * ditolak. `CarbonImmutable::parse()` yang dulu dipakai melempar galat untuk
     * teks sembarang (500) dan menerima `2026-02-31` sebagai 3 Maret.
     */
    public static function tanggalMasukan(mixed $nilai, string $zona): ?CarbonImmutable
    {
        if (! is_string($nilai) || preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $nilai, $bagian) !== 1) {
            return null;
        }

        [, $tahun, $bulan, $hari] = $bagian;
        if ((int) $tahun < 1900 || (int) $tahun > 2999 || ! checkdate((int) $bulan, (int) $hari, (int) $tahun)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', "{$tahun}-{$bulan}-{$hari}", $zona) ?: null;
    }

    /** Apakah masukan tanggal ada tetapi tidak dapat dibaca (lihat tanggalMasukan()). */
    public static function tanggalTidakSah(mixed $nilai, string $zona): bool
    {
        return $nilai !== null && $nilai !== '' && self::tanggalMasukan($nilai, $zona) === null;
    }

    /**
     * Salinan yang rentangnya paling panjang `$maksHari` hari kalender, dipotong
     * dari awal: hari terakhir yang diminta tetap, sehingga yang tampil adalah
     * bagian terbaru rentang itu.
     */
    public function dibatasiHari(int $maksHari): self
    {
        if ($maksHari < 1 || $this->jumlahHari() <= $maksHari) {
            return $this;
        }

        $dari = CarbonImmutable::parse($this->tanggalSampai(), $this->zona)
            ->subDays($maksHari - 1)
            ->startOfDay()
            ->utc();

        return new self(
            $dari,
            $this->sampai,
            $this->unitOrganisasiId,
            $this->lokasiId,
            $this->zona,
            $this->unitPengelolaId,
        );
    }

    /**
     * Seluruh nilai yang menentukan hasil KPI, dalam bentuk kanonik untuk kunci
     * cache: momen persis (bukan hanya tanggal) dan daftar id yang diurutkan,
     * supaya `[a, b]` dan `[b, a]` berbagi entri tetapi filter berbeda tidak pernah.
     *
     * @return array{Dari: string, Sampai: string, Zona: string, UnitOrganisasiId: list<string>, LokasiId: list<string>, UnitPengelolaId: list<string>}
     */
    public function sidik(): array
    {
        $urut = static function (array $daftar): array {
            $daftar = array_values(array_unique($daftar));
            sort($daftar, SORT_STRING);

            return $daftar;
        };

        return [
            'Dari' => $this->dari->utc()->format('Y-m-d H:i:s.u'),
            'Sampai' => $this->sampai->utc()->format('Y-m-d H:i:s.u'),
            'Zona' => $this->zona,
            'UnitOrganisasiId' => $urut($this->unitOrganisasiId),
            'LokasiId' => $urut($this->lokasiId),
            'UnitPengelolaId' => $urut($this->unitPengelolaId),
        ];
    }

    /**
     * Salinan dengan unit pengelola diganti, mis. setelah nilai yang bukan
     * unit pengelola organisasi ini dibuang.
     *
     * @param  list<string>  $unitPengelolaId
     */
    public function denganUnitPengelola(array $unitPengelolaId): self
    {
        return new self(
            $this->dari,
            $this->sampai,
            $this->unitOrganisasiId,
            $this->lokasiId,
            $this->zona,
            array_values(array_unique($unitPengelolaId)),
        );
    }

    /**
     * Id dari kueri URL. Nilai bersarang (`?UnitPengelolaId[0][]=x`) dibuang,
     * bukan diubah paksa menjadi teks "Array".
     *
     * @return list<string>
     */
    private static function daftarId(mixed $nilai): array
    {
        $daftar = array_filter((array) $nilai, fn (mixed $satu): bool => is_string($satu) || is_int($satu));

        return array_values(array_filter(array_map('strval', $daftar), fn (string $satu): bool => $satu !== ''));
    }

    public function adaFilterUnit(): bool
    {
        return $this->unitOrganisasiId !== [];
    }

    public function adaFilterLokasi(): bool
    {
        return $this->lokasiId !== [];
    }

    public function adaFilterUnitPengelola(): bool
    {
        return $this->unitPengelolaId !== [];
    }

    /** Tanggal kalender hari pertama rentang (Y-m-d), untuk kolom `date`. */
    public function tanggalDari(): string
    {
        return $this->dari->setTimezone($this->zona)->toDateString();
    }

    /** Tanggal kalender hari terakhir rentang (Y-m-d), untuk kolom `date`. */
    public function tanggalSampai(): string
    {
        return $this->sampai->setTimezone($this->zona)->toDateString();
    }

    /**
     * Tanggal kalender hari ini di organisasi, sebagai tengah malam UTC.
     *
     * Bentuknya sama dengan `KalenderOrganisasi::hariIni()` dan nilai kolom
     * `date`, jadi dapat dibandingkan langsung.
     */
    public function hariIni(): CarbonImmutable
    {
        return CarbonImmutable::parse(CarbonImmutable::now($this->zona)->toDateString(), 'UTC');
    }

    /**
     * Selisih zona terhadap UTC untuk SQL, mis. `+07:00`.
     *
     * Untuk mengelompokkan kolom waktu per tanggal lokal dengan
     * `CONVERT_TZ(kolom, '+00:00', offset)`. Zona Indonesia tidak mengenal
     * waktu musim panas, jadi satu selisih berlaku untuk seluruh rentang.
     */
    public function offsetSql(): string
    {
        return $this->sampai->setTimezone($this->zona)->format('P');
    }

    /** Jumlah hari kalender dalam rentang, minimal satu supaya tidak ada pembagian nol. */
    public function jumlahHari(): int
    {
        return max(1, (int) CarbonImmutable::parse($this->tanggalDari())->diffInDays(CarbonImmutable::parse($this->tanggalSampai())) + 1);
    }

    /** @return array<string, mixed> */
    public function keArray(): array
    {
        return [
            'Dari' => $this->tanggalDari(),
            'Sampai' => $this->tanggalSampai(),
            'UnitOrganisasiId' => $this->unitOrganisasiId,
            'LokasiId' => $this->lokasiId,
            'UnitPengelolaId' => $this->unitPengelolaId,
        ];
    }
}
