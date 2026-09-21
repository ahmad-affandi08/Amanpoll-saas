<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FiturPaket extends ModelDasar
{
    protected $table = 'FiturPaket';

    public $timestamps = false;

    protected $fillable = [
        'Kode',
        'Nama',
        'Deskripsi',
        'TipeBatas',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<PaketFitur, $this> */
    public function paketFitur(): HasMany
    {
        return $this->hasMany(PaketFitur::class, 'FiturPaketId', 'Id');
    }
}
