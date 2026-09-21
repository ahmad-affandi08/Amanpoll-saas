<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TagihanLangganan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'TagihanLangganan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'LanggananId',
        'Nomor',
        'PeriodeMulai',
        'PeriodeSelesai',
        'JatuhTempo',
        'Subtotal',
        'Pajak',
        'Total',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'PeriodeMulai' => 'immutable_date',
            'PeriodeSelesai' => 'immutable_date',
            'JatuhTempo' => 'immutable_date',
            'Subtotal' => 'decimal:2',
            'Pajak' => 'decimal:2',
            'Total' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<Langganan, $this> */
    public function langganan(): BelongsTo
    {
        return $this->belongsTo(Langganan::class, 'LanggananId', 'Id');
    }

    /** @return HasMany<PembayaranLangganan, $this> */
    public function pembayaran(): HasMany
    {
        return $this->hasMany(PembayaranLangganan::class, 'TagihanLanggananId', 'Id');
    }
}
