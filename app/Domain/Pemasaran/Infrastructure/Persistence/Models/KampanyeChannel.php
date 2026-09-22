<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Channel tempat satu kampanye dijalankan (MARKETING.md 13). */
final class KampanyeChannel extends ModelDasar
{
    protected $table = 'KampanyeChannel';

    public $timestamps = false;

    protected $fillable = [
        'KampanyeId',
        'Channel',
    ];

    protected function casts(): array
    {
        return ['DibuatPada' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Kampanye, $this> */
    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(Kampanye::class, 'KampanyeId', 'Id');
    }
}
