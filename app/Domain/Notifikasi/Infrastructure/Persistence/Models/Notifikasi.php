<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Notifikasi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'Notifikasi';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PenggunaId',
        'Kanal',
        'JenisPeristiwa',
        'Judul',
        'Isi',
        'JenisEntitas',
        'EntitasId',
        'Status',
        'JadwalKirimPada',
        'DikirimPada',
        'DibacaPada',
        'Percobaan',
        'KesalahanTerakhir',
    ];

    protected function casts(): array
    {
        return [
            'JadwalKirimPada' => 'immutable_datetime',
            'DikirimPada' => 'immutable_datetime',
            'DibacaPada' => 'immutable_datetime',
            'Percobaan' => 'integer',
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
