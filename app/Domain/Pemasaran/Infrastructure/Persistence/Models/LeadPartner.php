<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusLeadPartner;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu lead kiriman partner, dari pengiriman sampai pembayaran pertamanya (MARKETING.md 21). */
final class LeadPartner extends ModelDasar
{
    protected $table = 'LeadPartner';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'PartnerId',
        'ProspekId',
        'OrganisasiId',
        'NamaPerusahaan',
        'NamaKontak',
        'Email',
        'Telepon',
        'Catatan',
        'Status',
        'AlasanDitolak',
        'DikirimPada',
        'DiterimaPada',
        'MenjadiTrialPada',
        'MenjadiPaidPada',
        'KedaluwarsaPada',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusLeadPartner::class,
            'DikirimPada' => 'immutable_datetime',
            'DiterimaPada' => 'immutable_datetime',
            'MenjadiTrialPada' => 'immutable_datetime',
            'MenjadiPaidPada' => 'immutable_datetime',
            'KedaluwarsaPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Partner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'PartnerId', 'Id');
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return HasMany<KomisiPartner, $this> */
    public function komisi(): HasMany
    {
        return $this->hasMany(KomisiPartner::class, 'LeadPartnerId', 'Id');
    }
}
