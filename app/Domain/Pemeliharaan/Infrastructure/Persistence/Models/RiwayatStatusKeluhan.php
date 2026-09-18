<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RiwayatStatusKeluhan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RiwayatStatusKeluhan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'KeluhanId',
        'StatusSebelum',
        'StatusSesudah',
        'Catatan',
        'DiubahOleh',
        'DiubahPada',
    ];

    protected function casts(): array
    {
        return [
            'DiubahPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function keluhan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan::class, 'KeluhanId', 'Id');
    }

    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DiubahOleh', 'Id');
    }

}
