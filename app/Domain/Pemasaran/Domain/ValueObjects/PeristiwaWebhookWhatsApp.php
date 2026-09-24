<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

/** Isi satu kiriman webhook WhatsApp yang sudah diterjemahkan dari format penyedianya (MARKETING.md 16). */
final readonly class PeristiwaWebhookWhatsApp
{
    /**
     * @param  list<StatusKirimanWhatsApp>  $status
     * @param  list<PesanMasukWhatsApp>  $pesanMasuk
     */
    public function __construct(
        public array $status = [],
        public array $pesanMasuk = [],
    ) {}
}
