<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Contracts;

use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;

/**
 * Keterangan penyedia layanan luar yang dapat dipilih di konsol platform (PRD 8.23).
 *
 * Adapter yang mengimplementasikannya di-tag `amanpoll.penyedia-layanan` di container
 * supaya muncul di konsol tanpa domain Platform mengenal domain pemakainya.
 */
interface DeskripsiPenyediaLayanan
{
    public function kategori(): KategoriPenyediaLayanan;

    /** Kode stabil yang disimpan di `PenyediaLayananPlatform.Kode`. */
    public function kode(): string;

    public function nama(): string;

    /** Satu kalimat untuk kartu di konsol; penyedia tidak resmi wajib menyebut risikonya. */
    public function keterangan(): string;

    /** WhatsApp: memakai API resmi Meta. Pembayaran: selalu true. */
    public function resmi(): bool;

    /** @return list<IsianKredensial> */
    public function isian(): array;

    public function mendukungModeUji(): bool;
}
