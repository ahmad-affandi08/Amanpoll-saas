<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
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

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function alurPersetujuan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan::class, 'AlurPersetujuanId', 'Id');
    }

    public function peran(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Peran::class, 'PeranId', 'Id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'PenggunaId', 'Id');
    }

}
