<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateWhatsAppPemasaran;

/** Naskah WhatsApp memakai kosakata variabel yang sama dengan email, supaya keduanya tidak menyimpang (MARKETING.md 15, 16). */
final class PerenderTemplateWhatsApp
{
    public function __construct(private readonly PerenderTemplateEmail $perenderEmail) {}

    public function render(TemplateWhatsAppPemasaran $template, Prospek $prospek): string
    {
        return $this->perenderEmail->ganti($template->IsiTeks, $this->perenderEmail->variabel($prospek));
    }

    /** @return list<string> */
    public function variabelDikenal(): array
    {
        return $this->perenderEmail->variabelDikenal();
    }

    /** @return list<string> */
    public function variabelAsing(string ...$naskah): array
    {
        return $this->perenderEmail->variabelAsing(...$naskah);
    }
}
