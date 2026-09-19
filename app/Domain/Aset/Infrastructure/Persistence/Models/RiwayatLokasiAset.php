<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RiwayatLokasiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RiwayatLokasiAset';

    public const JENIS_REGISTRASI = 'Registrasi';
    public const JENIS_MANUAL = 'Manual';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'LokasiAsalId',
        'LokasiTujuanId',
        'JenisPerpindahan',
        'ReferensiJenis',
        'ReferensiId',
        'Alasan',
        'DipindahkanOleh',
        'DipindahkanPada',
    ];

    protected function casts(): array
    {
        return [
            'DipindahkanPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<Aset, $this>
     */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi, $this>
     */
    public function lokasiAsal(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiAsalId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi, $this>
     */
    public function lokasiTujuan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiTujuanId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna, $this>
     */
    public function dipindahkanOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DipindahkanOleh', 'Id');
    }

}
