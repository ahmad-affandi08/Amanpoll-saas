<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\JenisPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPartner;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/** Satu partner sekaligus identitas yang masuk ke portal partner (MARKETING.md 21). */
final class Partner extends Authenticatable
{
    use HasUlids;

    protected $table = 'Partner';

    protected $primaryKey = 'Id';

    protected $keyType = 'string';

    public $incrementing = false;

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'ProgramPartnerId',
        'Kode',
        'NamaPerusahaan',
        'Jenis',
        'NamaPic',
        'EmailPic',
        'TeleponPic',
        'KataSandi',
        'Status',
        'ReferensiPerjanjian',
        'ReferensiPayout',
        'TerakhirMasukPada',
    ];

    protected $hidden = ['KataSandi', 'TokenIngat'];

    protected function casts(): array
    {
        return [
            'Jenis' => JenisPartner::class,
            'Status' => StatusPartner::class,
            'TerakhirMasukPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'KataSandi' => 'hashed',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'KataSandi';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->KataSandi;
    }

    public function getRememberTokenName(): string
    {
        return 'TokenIngat';
    }

    public function getRouteKeyName(): string
    {
        return 'Id';
    }

    /** @return BelongsTo<ProgramPartner, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ProgramPartner::class, 'ProgramPartnerId', 'Id');
    }

    /** @return HasMany<LeadPartner, $this> */
    public function lead(): HasMany
    {
        return $this->hasMany(LeadPartner::class, 'PartnerId', 'Id');
    }

    /** @return HasMany<KomisiPartner, $this> */
    public function komisi(): HasMany
    {
        return $this->hasMany(KomisiPartner::class, 'PartnerId', 'Id');
    }

    /** @return HasMany<PayoutPartner, $this> */
    public function payout(): HasMany
    {
        return $this->hasMany(PayoutPartner::class, 'PartnerId', 'Id');
    }
}
