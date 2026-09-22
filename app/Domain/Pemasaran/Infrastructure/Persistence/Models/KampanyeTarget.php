<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\MetrikTargetKampanye;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Angka yang hendak dicapai satu kampanye pada satu metrik (MARKETING.md 13). */
final class KampanyeTarget extends ModelDasar
{
    protected $table = 'KampanyeTarget';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'KampanyeId',
        'Metrik',
        'Nilai',
    ];

    protected function casts(): array
    {
        return [
            'Metrik' => MetrikTargetKampanye::class,
            'Nilai' => 'decimal:2',
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
