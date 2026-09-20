<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'PeriodeMulai' => 'date',
            'PeriodeSelesai' => 'date',
            'JatuhTempo' => 'date',
            'Subtotal' => 'decimal:2',
            'Pajak' => 'decimal:2',
            'Total' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function langganan(): BelongsTo
    {
        return $this->belongsTo(Langganan::class, 'LanggananId', 'Id');
    }
}
