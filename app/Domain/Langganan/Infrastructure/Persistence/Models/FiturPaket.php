<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;

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
}
