<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Parameter kampanye satu kedatangan (MARKETING.md 14). */
final class UtmPemasaran extends ModelDasar
{
    protected $table = 'UtmPemasaran';

    public $timestamps = false;

    protected $fillable = [
        'SesiPengunjungId',
        'KampanyeId',
        'Source',
        'Medium',
        'Campaign',
        'Term',
        'Content',
    ];

    protected function casts(): array
    {
        return ['DirekamPada' => 'immutable_datetime'];
    }

    /** @return BelongsTo<SesiPengunjung, $this> */
    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiPengunjung::class, 'SesiPengunjungId', 'Id');
    }

    /** @return BelongsTo<Kampanye, $this> */
    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(Kampanye::class, 'KampanyeId', 'Id');
    }
}
