<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class KategoriAset extends ModelDasar
{
    use MilikOrganisasi, SoftDeletes;

    protected $table = 'KategoriAset';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'IndukId',
        'Kode',
        'Nama',
        'UmurManfaatBulan',
        'MetodePenyusutanBawaan',
        'PersentaseNilaiResidu',
        'MemerlukanKalibrasi',
        'MemerlukanPemeliharaan',
    ];

    protected function casts(): array
    {
        return [
            'UmurManfaatBulan' => 'integer',
            'PersentaseNilaiResidu' => 'decimal:4',
            'MemerlukanKalibrasi' => 'boolean',
            'MemerlukanPemeliharaan' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
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
     * @return BelongsTo<KategoriAset, $this>
     */
    public function induk(): BelongsTo
    {
        return $this->belongsTo(KategoriAset::class, 'IndukId', 'Id');
    }

    /**
     * @return HasMany<KategoriAset, $this>
     */
    public function anak(): HasMany
    {
        return $this->hasMany(KategoriAset::class, 'IndukId', 'Id');
    }
}
