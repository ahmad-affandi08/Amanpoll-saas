<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LaporanTersimpan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'LaporanTersimpan';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nama',
        'Jenis',
        'Konfigurasi',
        'Pribadi',
        'PemilikId',
    ];

    protected function casts(): array
    {
        return [
            'Konfigurasi' => 'array',
            'Pribadi' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PemilikId', 'Id');
    }

    /** Nama pemilik bila relasinya sudah dimuat; null untuk laporan tanpa pemilik. */
    public function namaPemilik(): ?string
    {
        $pemilik = $this->pemilik;

        return $pemilik instanceof Pengguna ? $pemilik->Nama : null;
    }
}
