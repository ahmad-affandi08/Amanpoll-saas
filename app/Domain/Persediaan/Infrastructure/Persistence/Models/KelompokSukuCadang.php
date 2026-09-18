<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KelompokSukuCadang extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KelompokSukuCadang';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'SukuCadangId',
        'NomorBatch',
        'TanggalProduksi',
        'TanggalKadaluarsa',
        'HargaPerolehan',
    ];

    protected function casts(): array
    {
        return [
            'TanggalProduksi' => 'date',
            'TanggalKadaluarsa' => 'date',
            'HargaPerolehan' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang::class, 'SukuCadangId', 'Id');
    }

}
