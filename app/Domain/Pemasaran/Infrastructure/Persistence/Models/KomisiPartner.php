<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusKomisiPartner;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu komisi milik satu pembayaran yang sungguh terjadi; `PembayaranId` unik menahan kelahiran keduanya (MARKETING.md 21). */
final class KomisiPartner extends ModelDasar
{
    protected $table = 'KomisiPartner';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'PartnerId',
        'LeadPartnerId',
        'AturanKomisiPartnerId',
        'PayoutPartnerId',
        'OrganisasiId',
        'LanggananId',
        'PembayaranId',
        'JumlahPembayaran',
        'Jumlah',
        'Status',
        'Catatan',
        'DibayarPada',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusKomisiPartner::class,
            'JumlahPembayaran' => 'decimal:2',
            'Jumlah' => 'decimal:2',
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

    /** @return BelongsTo<LeadPartner, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadPartner::class, 'LeadPartnerId', 'Id');
    }

    /** @return BelongsTo<AturanKomisiPartner, $this> */
    public function aturan(): BelongsTo
    {
        return $this->belongsTo(AturanKomisiPartner::class, 'AturanKomisiPartnerId', 'Id');
    }

    /** @return BelongsTo<PayoutPartner, $this> */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(PayoutPartner::class, 'PayoutPartnerId', 'Id');
    }
}
