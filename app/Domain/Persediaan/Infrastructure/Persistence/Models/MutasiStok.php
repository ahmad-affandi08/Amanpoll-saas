<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MutasiStok extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'MutasiStok';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'Jenis',
        'GudangAsalId',
        'GudangTujuanId',
        'ReferensiJenis',
        'ReferensiId',
        'Tanggal',
        'Status',
        'Catatan',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'Tanggal' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function gudangAsal(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang::class, 'GudangAsalId', 'Id');
    }

    public function gudangTujuan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang::class, 'GudangTujuanId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DibuatOleh', 'Id');
    }

}
