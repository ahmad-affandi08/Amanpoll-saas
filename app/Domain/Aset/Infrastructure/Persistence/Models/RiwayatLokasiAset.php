<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RiwayatLokasiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RiwayatLokasiAset';

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
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<Aset, $this>
     */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    /**
     * @return BelongsTo<Lokasi, $this>
     */
    public function lokasiAsal(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'LokasiAsalId', 'Id');
    }

    /**
     * @return BelongsTo<Lokasi, $this>
     */
    public function lokasiTujuan(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'LokasiTujuanId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function dipindahkanOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DipindahkanOleh', 'Id');
    }
}
