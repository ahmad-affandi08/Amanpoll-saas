<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusPayoutPartner;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu pembayaran komisi ke partner; transfernya terjadi di luar aplikasi dan hanya referensinya dicatat (MARKETING.md 21). */
final class PayoutPartner extends ModelDasar
{
    protected $table = 'PayoutPartner';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'PartnerId',
        'Nomor',
        'Jumlah',
        'JumlahKomisi',
        'Status',
        'ReferensiPembayaran',
        'Catatan',
        'DibayarPada',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusPayoutPartner::class,
            'Jumlah' => 'decimal:2',
            'JumlahKomisi' => 'integer',
            'DibayarPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Partner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'PartnerId', 'Id');
    }

    /** @return HasMany<KomisiPartner, $this> */
    public function komisi(): HasMany
    {
        return $this->hasMany(KomisiPartner::class, 'PayoutPartnerId', 'Id');
    }
}
