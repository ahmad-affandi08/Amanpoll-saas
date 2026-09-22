<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\HanyaTambah;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Perpindahan tahap satu prospek (MARKETING.md 5.3).
 *
 * Hanya-tambah: seluruh analisis corong menghitung lama tertahan per tahap dari
 * tabel ini, dan riwayat yang dapat ditulis ulang membuat angkanya tak berarti.
 */
final class RiwayatTahapProspek extends ModelDasar
{
    use HanyaTambah;

    protected $table = 'RiwayatTahapProspek';

    public $timestamps = false;

    protected $fillable = [
        'ProspekId',
        'TahapSebelumId',
        'TahapSesudahId',
        'AktorPlatformId',
        'Alasan',
        'BerpindahPada',
    ];

    protected function casts(): array
    {
        return ['BerpindahPada' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }

    /** @return BelongsTo<TahapPipeline, $this> */
    public function tahapSesudah(): BelongsTo
    {
        return $this->belongsTo(TahapPipeline::class, 'TahapSesudahId', 'Id');
    }

    /** @return BelongsTo<TahapPipeline, $this> */
    public function tahapSebelum(): BelongsTo
    {
        return $this->belongsTo(TahapPipeline::class, 'TahapSebelumId', 'Id');
    }
}
