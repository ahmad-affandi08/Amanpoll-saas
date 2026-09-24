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

        $dari = isset($data['Dari'])
            ? CarbonImmutable::parse(substr((string) $data['Dari'], 0, 10), $zona)
            : $hariIni->subDays(29);

        $sampai = isset($data['Sampai'])
            ? CarbonImmutable::parse(substr((string) $data['Sampai'], 0, 10), $zona)
            : $hariIni;

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
