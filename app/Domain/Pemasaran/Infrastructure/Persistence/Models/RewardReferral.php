<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\JenisRewardReferral;
use App\Domain\Pemasaran\Domain\Enums\StatusRewardReferral;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Imbalan yang terutang atau sudah diberikan atas satu referral (MARKETING.md 20). */
final class RewardReferral extends ModelDasar
{
    protected $table = 'RewardReferral';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'ReferralId',
        'OrganisasiPenerimaId',
        'Jenis',
        'Nilai',
        'Status',
        'Percobaan',
        'Ringkasan',
        'Galat',
        'DiberikanPada',
    ];

    protected function casts(): array
    {
        return [
            'Jenis' => JenisRewardReferral::class,
            'Nilai' => 'decimal:2',
            'Status' => StatusRewardReferral::class,
            'Percobaan' => 'integer',
            'DiberikanPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Referral, $this> */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class, 'ReferralId', 'Id');
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function penerima(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiPenerimaId', 'Id');
    }
}
