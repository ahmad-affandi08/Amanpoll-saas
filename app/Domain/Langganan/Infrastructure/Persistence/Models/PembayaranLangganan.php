<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PembayaranLangganan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PembayaranLangganan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'TagihanLanggananId',
        'PenyediaPembayaran',
        'ReferensiEksternal',
        'Metode',
        'Jumlah',
        'Status',
        'DibayarPada',
        'MuatanData',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:2',
            'DibayarPada' => 'immutable_datetime',
            'MuatanData' => 'array',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function tagihanLangganan(): BelongsTo
    {
        return $this->belongsTo(TagihanLangganan::class, 'TagihanLanggananId', 'Id');
    }
}
