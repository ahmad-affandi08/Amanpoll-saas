<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\IntentKeyword;
use App\Domain\Pemasaran\Domain\Enums\PrioritasKeyword;
use App\Domain\Pemasaran\Domain\Enums\StatusKeywordSeo;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu keyword beserta niat pencarian dan targetnya (MARKETING.md 9). */
final class KeywordSeo extends ModelDasar
{
    protected $table = 'KeywordSeo';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Keyword',
        'ClusterSeoId',
        'Intent',
        'TargetUrl',
        'Prioritas',
        'Status',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'Intent' => IntentKeyword::class,
            'Prioritas' => PrioritasKeyword::class,
            'Status' => StatusKeywordSeo::class,
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ClusterSeo, $this> */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(ClusterSeo::class, 'ClusterSeoId', 'Id');
    }

    /** @return HasMany<KontenKeywordSeo, $this> */
    public function tautanKonten(): HasMany
    {
        return $this->hasMany(KontenKeywordSeo::class, 'KeywordSeoId', 'Id');
    }
}
