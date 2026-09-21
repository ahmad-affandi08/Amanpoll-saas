<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TahapPersetujuan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'TahapPersetujuan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'AlurPersetujuanId',
        'Urutan',
        'Nama',
        'JenisPenyetuju',
        'PeranId',
        'PenggunaId',
        'JumlahMinimumPenyetuju',
        'BolehMenyetujuiSendiri',
        'BatasWaktuMenit',
        'Kondisi',
    ];

    protected function casts(): array
    {
        return [
            'Urutan' => 'integer',
            'JumlahMinimumPenyetuju' => 'integer',
            'BolehMenyetujuiSendiri' => 'boolean',
            'BatasWaktuMenit' => 'integer',
            'Kondisi' => 'array',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<AlurPersetujuan, $this> */
    public function alurPersetujuan(): BelongsTo
    {
        return $this->belongsTo(AlurPersetujuan::class, 'AlurPersetujuanId', 'Id');
    }

    /** @return BelongsTo<Peran, $this> */
    public function peran(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'PeranId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PenggunaId', 'Id');
    }
}
