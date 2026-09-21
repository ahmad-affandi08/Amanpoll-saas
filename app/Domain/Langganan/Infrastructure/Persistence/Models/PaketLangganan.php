<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PaketLangganan extends ModelDasar
{
    protected $table = 'PaketLangganan';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'Deskripsi',
        'HargaBulanan',
        'HargaTahunan',
        'MataUang',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'HargaBulanan' => 'decimal:2',
            'HargaTahunan' => 'decimal:2',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<PaketFitur, $this> */
    public function fitur(): HasMany
    {
        return $this->hasMany(PaketFitur::class, 'PaketLanggananId', 'Id');
    }

    /** @return HasMany<Langganan, $this> */
    public function langganan(): HasMany
    {
        return $this->hasMany(Langganan::class, 'PaketLanggananId', 'Id');
    }
}
