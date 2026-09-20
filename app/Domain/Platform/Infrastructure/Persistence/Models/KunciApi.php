<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KunciApi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KunciApi';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'Nama',
        'AwalanKunci',
        'HashKunci',
        'Cakupan',
        'AlamatIpDiizinkan',
        'KadaluarsaPada',
        'TerakhirDipakaiPada',
        'Status',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'Cakupan' => 'array',
            'AlamatIpDiizinkan' => 'array',
            'KadaluarsaPada' => 'immutable_datetime',
            'TerakhirDipakaiPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
    }
}
