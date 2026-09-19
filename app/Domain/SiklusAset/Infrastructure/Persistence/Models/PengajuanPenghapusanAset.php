<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PengajuanPenghapusanAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PengajuanPenghapusanAset';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    public const STATUS_DRAFT = 'Draft';
    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_DISETUJUI = 'Disetujui';
    public const STATUS_DITOLAK = 'Ditolak';
    public const STATUS_DIBATALKAN = 'Dibatalkan';
    public const STATUS_SELESAI = 'Selesai';

    public const METODE_DIJUAL = 'Dijual';
    public const METODE_DIMUSNAHKAN = 'Dimusnahkan';
    public const METODE_HIBAH = 'Hibah';
    public const METODE_HILANG = 'Hilang';
    public const METODE_LAINNYA = 'Lainnya';

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
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna, $this>
     */
    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DiajukanOleh', 'Id');
    }

    /**
     * @return HasMany<DetailPenghapusanAset, $this>
     */
    public function detailPenghapusanAset(): HasMany
    {
        return $this->hasMany(DetailPenghapusanAset::class, 'PengajuanPenghapusanAsetId', 'Id');
    }

}
