<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KepatuhanAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KepatuhanAset';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'PersyaratanKepatuhanId',
        'Status',
        'TanggalPemeriksaan',
        'BerlakuSampai',
        'Catatan',
        'DiperiksaOleh',
    ];

    protected function casts(): array
    {
        return [
            'TanggalPemeriksaan' => 'date',
            'BerlakuSampai' => 'date',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

    public function persyaratanKepatuhan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PersyaratanKepatuhan::class, 'PersyaratanKepatuhanId', 'Id');
    }

    public function diperiksaOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DiperiksaOleh', 'Id');
    }

}
