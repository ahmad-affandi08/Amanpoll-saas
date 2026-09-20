<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PeranIzin extends ModelDasar
{
    protected $table = 'PeranIzin';

    public $timestamps = false;

    protected $fillable = [
        'PeranId',
        'IzinId',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function peran(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'PeranId', 'Id');
    }

    public function izin(): BelongsTo
    {
        return $this->belongsTo(Izin::class, 'IzinId', 'Id');
    }
}
