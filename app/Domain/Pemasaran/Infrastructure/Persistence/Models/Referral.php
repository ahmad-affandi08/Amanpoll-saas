<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusReferral;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Perjalanan satu orang yang diajak, dari klik sampai imbalannya (MARKETING.md 20). */
final class Referral extends ModelDasar
{
    protected $table = 'Referral';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'ProgramReferralId',
        'KodeReferralId',
        'OrganisasiPerujukId',
        'PengenalPengunjung',
        'ProspekId',
        'OrganisasiBaruId',
        'LanggananId',
        'Status',
        'AlasanDitolak',
        'DiklikPada',
        'MenjadiLeadPada',
        'MenjadiTrialPada',
        'MenjadiPaidPada',
        'KedaluwarsaPada',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusReferral::class,
            'DiklikPada' => 'immutable_datetime',
            'MenjadiLeadPada' => 'immutable_datetime',
            'MenjadiTrialPada' => 'immutable_datetime',
            'MenjadiPaidPada' => 'immutable_datetime',
            'KedaluwarsaPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ProgramReferral, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ProgramReferral::class, 'ProgramReferralId', 'Id');
    }

    /** @return BelongsTo<KodeReferral, $this> */
    public function kode(): BelongsTo
    {
        return $this->belongsTo(KodeReferral::class, 'KodeReferralId', 'Id');
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function perujuk(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiPerujukId', 'Id');
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }

    /** @return HasOne<RewardReferral, $this> */
    public function reward(): HasOne
    {
        return $this->hasOne(RewardReferral::class, 'ReferralId', 'Id');
    }
}
