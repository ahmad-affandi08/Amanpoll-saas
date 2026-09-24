<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Contracts;

use App\Domain\Pemasaran\Domain\ValueObjects\PeristiwaWebhookWhatsApp;

/** Penyedia WhatsApp yang mengabarkan status kiriman dan pesan masuk lewat webhook (MARKETING.md 16). */
interface PenerimaWebhookWhatsApp
{
    /**
     * Jawaban atas permintaan verifikasi langganan webhook (GET); null berarti ditolak.
     *
     * @param  array<string, mixed>  $kueri
     */
    public function verifikasiLangganan(array $kueri): ?string;

    /**
     * Apakah kiriman webhook ini benar dari penyedia; badan mentah dibutuhkan untuk tanda tangan HMAC.
     *
     * @param  array<string, string>  $header  Nama header dalam huruf kecil.
     * @param  array<string, mixed>  $kueri
     */
    public function webhookSah(string $badanMentah, array $header, array $kueri): bool;

    /** @param array<mixed> $muatan */
    public function terjemahkanWebhook(array $muatan): PeristiwaWebhookWhatsApp;
}
