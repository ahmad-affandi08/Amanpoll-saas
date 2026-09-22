<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Events;

/**
 * Peristiwa langganan yang boleh didengar domain lain.
 *
 * Satu kelas dengan kode, bukan satu kelas per peristiwa: yang membutuhkannya
 * hanyalah pendengar yang memetakan kode ke taxonomy pemasaran, dan tujuh kelas
 * kosong hanya menambah berkas tanpa menambah keterangan.
 */
final readonly class PeristiwaLangganan
{
    public const LANGGANAN_DIBUAT = 'LanggananDibuat';

    public const LANGGANAN_DIBATALKAN = 'LanggananDibatalkan';

    public const PEMBAYARAN_BERHASIL = 'PembayaranBerhasil';

    public const PEMBAYARAN_GAGAL = 'PembayaranGagal';

    public const UPGRADE_DILAKUKAN = 'UpgradeDilakukan';

    public const DOWNGRADE_DILAKUKAN = 'DowngradeDilakukan';

    /** @param array<string, mixed> $data */
    public function __construct(
        public string $kode,
        public string $organisasiId,
        public ?string $langgananId = null,
        public array $data = [],
    ) {}
}
