<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DasborTersimpan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DasborTersimpan';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nama',
        'PemilikId',
        'Bawaan',
        'Konfigurasi',
    ];

    protected function casts(): array
    {
        return [
            'Bawaan' => 'boolean',
            'Konfigurasi' => 'array',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PemilikId', 'Id');
    }

    /** @return HasMany<KomponenDasbor, $this> */
    public function komponen(): HasMany
    {
        return $this->hasMany(KomponenDasbor::class, 'DasborTersimpanId', 'Id')->orderBy('Urutan');
    }
}
