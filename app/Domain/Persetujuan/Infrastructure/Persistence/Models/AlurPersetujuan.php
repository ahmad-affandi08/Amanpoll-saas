<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AlurPersetujuan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'AlurPersetujuan';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'JenisEntitas',
        'KondisiAktivasi',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'KondisiAktivasi' => 'array',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return HasMany<TahapPersetujuan, $this>
     */
    public function tahapPersetujuan(): HasMany
    {
        return $this->hasMany(TahapPersetujuan::class, 'AlurPersetujuanId', 'Id')->orderBy('Urutan');
    }

}
