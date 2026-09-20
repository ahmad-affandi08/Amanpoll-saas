<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;

final class Izin extends ModelDasar
{
    protected $table = 'Izin';

    public $timestamps = false;

    protected $fillable = [
        'Kode',
        'Nama',
        'Modul',
        'Keterangan',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
        ];
    }
}
