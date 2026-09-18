<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenyediaKategori extends ModelDasar
{
    protected $table = 'PenyediaKategori';

    public $timestamps = false;

    protected $fillable = [
        'PenyediaId',
        'KategoriPenyediaId',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia::class, 'PenyediaId', 'Id');
    }

    public function kategoriPenyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia::class, 'KategoriPenyediaId', 'Id');
    }

}
