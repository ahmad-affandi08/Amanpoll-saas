<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LampiranEntitas extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'LampiranEntitas';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'JenisEntitas',
        'EntitasId',
        'BerkasId',
        'Kategori',
        'Keterangan',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas::class, 'BerkasId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DibuatOleh', 'Id');
    }

}
