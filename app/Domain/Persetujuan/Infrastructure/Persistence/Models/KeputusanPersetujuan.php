<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KeputusanPersetujuan extends ModelDasar
{
    use MilikOrganisasi;

    public const KEPUTUSAN_DISETUJUI = 'Disetujui';
    public const KEPUTUSAN_DITOLAK = 'Ditolak';

    protected $table = 'KeputusanPersetujuan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PermintaanPersetujuanId',
        'TahapPersetujuanId',
        'PenyetujuId',
        'Keputusan',
        'Catatan',
        'DiputuskanPada',
    ];

    protected function casts(): array
    {
        return [
            'DiputuskanPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function permintaanPersetujuan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan::class, 'PermintaanPersetujuanId', 'Id');
    }

    public function tahapPersetujuan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan::class, 'TahapPersetujuanId', 'Id');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'PenyetujuId', 'Id');
    }

}
