<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Contracts;

/** Satu-satunya pintu domain lain memberi imbalan pada langganan; Billing tidak pernah ditulis dari luar (MARKETING.md 20). */
interface PemberiImbalanLangganan
{
    public function mendukung(string $jenisImbalan): bool;

    /**
     * Memberi imbalan pada satu organisasi; kembaliannya ringkasan satu baris untuk audit.
     *
     * @param  array<string, mixed>  $rincian
     */
    public function beri(string $organisasiId, string $jenisImbalan, float $nilai, array $rincian = []): string;
}
