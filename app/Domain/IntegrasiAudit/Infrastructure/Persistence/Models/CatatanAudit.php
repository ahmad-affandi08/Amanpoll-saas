<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\HanyaTambah;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CatatanAudit extends ModelDasar
{
    use HanyaTambah, MilikOrganisasi;

    protected $table = 'CatatanAudit';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PenggunaId',
        'AktorPlatformId',
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

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PenggunaId', 'Id');
    }
}
