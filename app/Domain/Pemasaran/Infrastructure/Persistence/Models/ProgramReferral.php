<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Pemasaran\Domain\Enums\JenisRewardReferral;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu program referral beserta imbalan yang dijanjikannya (MARKETING.md 20). */
final class ProgramReferral extends ModelDasar
{
    use PunyaKodeOtomatis;

    protected $table = 'ProgramReferral';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'Keterangan',
        'JenisReward',
        'NilaiReward',
        'HariKedaluwarsa',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'JenisReward' => JenisRewardReferral::class,
            'NilaiReward' => 'decimal:2',
            'HariKedaluwarsa' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'REF';
    }

    public function getRouteKeyName(): string
    {
        return 'Kode';
    }

    /** @return HasMany<KodeReferral, $this> */
    public function kode(): HasMany
    {
        return $this->hasMany(KodeReferral::class, 'ProgramReferralId', 'Id');
    }

    /** @return HasMany<Referral, $this> */
    public function referral(): HasMany
    {
        return $this->hasMany(Referral::class, 'ProgramReferralId', 'Id');
    }
}
