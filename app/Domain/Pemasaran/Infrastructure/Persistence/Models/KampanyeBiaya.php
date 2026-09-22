<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\ChannelKampanye;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Belanja satu kampanye pada satu channel pada satu hari (MARKETING.md 13). */
final class KampanyeBiaya extends ModelDasar
{
    protected $table = 'KampanyeBiaya';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'KampanyeId',
        'Channel',
        'Tanggal',
        'Jumlah',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'Channel' => ChannelKampanye::class,
            'Tanggal' => 'immutable_date',
            'Jumlah' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Kampanye, $this> */
    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(Kampanye::class, 'KampanyeId', 'Id');
    }
}
