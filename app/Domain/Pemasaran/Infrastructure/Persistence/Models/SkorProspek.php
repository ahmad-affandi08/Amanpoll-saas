<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sumbangan satu peristiwa terhadap skor prospek (MARKETING.md 5.4).
 *
 * Disimpan per peristiwa, bukan hanya totalnya, supaya angka pada kartu prospek
 * dapat dijelaskan. Skor yang tidak dapat dijelaskan akan diabaikan tim
 * penjualan, dan skor yang diabaikan tidak ada gunanya dihitung.
 */
final class SkorProspek extends ModelDasar
{
    protected $table = 'SkorProspek';

    public $timestamps = false;

    protected $fillable = ['ProspekId', 'Peristiwa', 'Bobot', 'DihitungPada'];

    protected function casts(): array
    {
        return ['Bobot' => 'integer', 'DihitungPada' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }
}
