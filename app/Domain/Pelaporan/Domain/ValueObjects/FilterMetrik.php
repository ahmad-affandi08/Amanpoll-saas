<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\ValueObjects;

use Carbon\CarbonImmutable;

/**
 * Irisan data yang berlaku untuk seluruh KPI pada satu tampilan (21.02):
 * rentang tanggal plus penyaring unit organisasi dan lokasi.
 *
 * Filter ini sengaja tidak memuat OrganisasiId: pembatasan tenant dipegang
 * global scope MilikOrganisasi, bukan oleh pemanggil.
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
    ) {}

    /** Rentang bawaan dasbor: 30 hari terakhir sampai akhir hari ini. */
    public static function bawaan(): self
    {
        return new self(
            CarbonImmutable::now()->subDays(29)->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
        );
    }

    /** @param array<string, mixed> $data */
    public static function dariArray(array $data): self
    {
        $dari = isset($data['Dari'])
            ? CarbonImmutable::parse((string) $data['Dari'])->startOfDay()
            : CarbonImmutable::now()->subDays(29)->startOfDay();

        $sampai = isset($data['Sampai'])
            ? CarbonImmutable::parse((string) $data['Sampai'])->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        // Rentang terbalik dinormalkan daripada menghasilkan laporan kosong
        // yang membingungkan.
        if ($sampai->lessThan($dari)) {
            [$dari, $sampai] = [$sampai->startOfDay(), $dari->endOfDay()];
        }

        return new self(
            $dari,
            $sampai,
            array_values(array_filter(array_map('strval', (array) ($data['UnitOrganisasiId'] ?? [])))),
            array_values(array_filter(array_map('strval', (array) ($data['LokasiId'] ?? [])))),
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

    /** Jumlah hari dalam rentang, minimal satu supaya tidak ada pembagian nol. */
    public function jumlahHari(): int
    {
        return max(1, (int) $this->dari->startOfDay()->diffInDays($this->sampai->endOfDay()) + 1);
    }

    /** @return array<string, mixed> */
    public function keArray(): array
    {
        return [
            'Dari' => $this->dari->toDateString(),
            'Sampai' => $this->sampai->toDateString(),
            'UnitOrganisasiId' => $this->unitOrganisasiId,
            'LokasiId' => $this->lokasiId,
        ];
    }
}
