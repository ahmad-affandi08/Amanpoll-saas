<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Services;

use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\ValueObjects\NomorWhatsApp;

/**
 * Dua syarat kanal WhatsApp di luar preferensi: penyedia WhatsApp platform sedang aktif
 * dan penerimanya punya nomor telepon yang dapat dipakai WhatsApp (PRD 8.15).
 */
final class TujuanWhatsAppNotifikasi
{
    public function __construct(private readonly PembacaKredensialPenyedia $pembaca) {}

    /**
     * Penyedia utama yang aktif di konsol; penyedia log bawaan tidak dihitung karena tidak mengantar apa pun.
     * Tidak disimpan di instance: layanan ini ikut hidup di singleton dan worker antrian.
     */
    public function penyediaAktif(): bool
    {
        return $this->pembaca->kodeUtama(KategoriPenyediaLayanan::WhatsApp) !== null;
    }

    public function nomorUntuk(string $penggunaId): ?string
    {
        $telepon = Pengguna::query()->whereKey($penggunaId)->value('Telepon');

        return NomorWhatsApp::internasional(is_string($telepon) ? $telepon : null);
    }
}
