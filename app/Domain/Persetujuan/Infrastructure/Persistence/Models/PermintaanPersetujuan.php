<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PermintaanPersetujuan extends ModelDasar
{
    use MilikOrganisasi;

    public const STATUS_MENUNGGU = 'Menunggu';

    public const STATUS_DISETUJUI = 'Disetujui';

    public const STATUS_DITOLAK = 'Ditolak';

    public const STATUS_DIBATALKAN = 'Dibatalkan';

    protected $table = 'PermintaanPersetujuan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'AlurPersetujuanId',
        'JenisEntitas',
        'EntitasId',
        'TahapSaatIni',
        'Status',
        'DimintaOleh',
        'DimintaPada',
        'SelesaiPada',
        'DataTambahan',
    ];

    protected function casts(): array
    {
        return [
            'TahapSaatIni' => 'integer',
            'DimintaPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
            'DataTambahan' => 'array',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function alurPersetujuan(): BelongsTo
    {
        return $this->belongsTo(AlurPersetujuan::class, 'AlurPersetujuanId', 'Id');
    }

    public function dimintaOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DimintaOleh', 'Id');
    }

    /**
     * @return HasMany<KeputusanPersetujuan, $this>
     */
    public function keputusan(): HasMany
    {
        return $this->hasMany(KeputusanPersetujuan::class, 'PermintaanPersetujuanId', 'Id');
    }
}
