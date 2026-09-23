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
 */
final readonly class FilterMetrik
{
    /**
     * @param  list<string>  $unitOrganisasiId
     * @param  list<string>  $lokasiId
     */
    public function __construct(
        public CarbonImmutable $dari,
        public CarbonImmutable $sampai,
        public array $unitOrganisasiId = [],
        public array $lokasiId = [],
        public string $zona = 'UTC',
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
            array_values(array_filter(array_map('strval', (array) ($data['UnitOrganisasiId'] ?? [])))),
            array_values(array_filter(array_map('strval', (array) ($data['LokasiId'] ?? [])))),
            $zona,
        );
    }

    public function adaFilterUnit(): bool
    {
        return $this->unitOrganisasiId !== [];
    }

    public function adaFilterLokasi(): bool
    {
        return $this->lokasiId !== [];
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
        ];
    }
}
