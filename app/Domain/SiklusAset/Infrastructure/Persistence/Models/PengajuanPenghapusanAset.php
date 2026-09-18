<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PengajuanPenghapusanAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PengajuanPenghapusanAset';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'Alasan',
        'MetodePenghapusan',
        'Status',
        'DiajukanOleh',
        'DiajukanPada',
        'DiselesaikanPada',
    ];

    protected function casts(): array
    {
        return [
            'DiajukanPada' => 'immutable_datetime',
            'DiselesaikanPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DiajukanOleh', 'Id');
    }

}
