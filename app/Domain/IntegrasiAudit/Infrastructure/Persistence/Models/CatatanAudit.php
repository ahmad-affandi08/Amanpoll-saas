<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CatatanAudit extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'CatatanAudit';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PenggunaId',
        'Aksi',
        'JenisEntitas',
        'EntitasId',
        'DataSebelum',
        'DataSesudah',
        'AlamatIp',
        'AgenPengguna',
        'KorelasiId',
    ];

    protected function casts(): array
    {
        return [
            'DataSebelum' => 'array',
            'DataSesudah' => 'array',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'PenggunaId', 'Id');
    }

}
