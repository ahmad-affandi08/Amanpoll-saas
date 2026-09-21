<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PengajuanPenghapusanAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PengajuanPenghapusanAset';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'Alasan',
        'MetodePenghapusan',
        'Status',
        'DiajukanOleh',
        'DiajukanPada',
        'DiselesaikanPada',
    ];

    protected function casts(): array
    {
        return [
            'DiajukanPada' => 'immutable_datetime',
            'DiselesaikanPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
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
     * @return BelongsTo<Pengguna, $this>
     */
    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DiajukanOleh', 'Id');
    }

    /**
     * @return HasMany<DetailPenghapusanAset, $this>
     */
    public function detailPenghapusanAset(): HasMany
    {
        return $this->hasMany(DetailPenghapusanAset::class, 'PengajuanPenghapusanAsetId', 'Id');
    }
}
