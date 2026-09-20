<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SerahTerimaAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'SerahTerimaAset';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const STATUS_DISERAHKAN = 'Diserahkan';

    public const STATUS_DITERIMA = 'Diterima';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'PermintaanMutasiAsetId',
        'Jenis',
        'PihakMenyerahkan',
        'PihakMenerima',
        'DiserahkanPada',
        'DiterimaPada',
        'Status',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'DiserahkanPada' => 'immutable_datetime',
            'DiterimaPada' => 'immutable_datetime',
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
     * @return BelongsTo<PermintaanMutasiAset, $this>
     */
    public function permintaanMutasiAset(): BelongsTo
    {
        return $this->belongsTo(PermintaanMutasiAset::class, 'PermintaanMutasiAsetId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function pihakMenyerahkan(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PihakMenyerahkan', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function pihakMenerima(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PihakMenerima', 'Id');
    }

    /**
     * @return HasMany<DetailSerahTerimaAset, $this>
     */
    public function detailSerahTerimaAset(): HasMany
    {
        return $this->hasMany(DetailSerahTerimaAset::class, 'SerahTerimaAsetId', 'Id');
    }
}
