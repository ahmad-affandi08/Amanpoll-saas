<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AntrianSinkronisasi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'AntrianSinkronisasi';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PerangkatPenggunaId',
        'KunciOperasi',
        'JenisEntitas',
        'EntitasId',
        'Operasi',
        'VersiKlien',
        'MuatanData',
        'Status',
        'Konflik',
        'Percobaan',
        'DiterimaPada',
        'DiprosesPada',
    ];

    protected function casts(): array
    {
        return [
            'VersiKlien' => 'integer',
            'MuatanData' => 'array',
            'Konflik' => 'array',
            'Percobaan' => 'integer',
            'DiterimaPada' => 'immutable_datetime',
            'DiprosesPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function perangkatPengguna(): BelongsTo
    {
        return $this->belongsTo(PerangkatPengguna::class, 'PerangkatPenggunaId', 'Id');
    }
}
