<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class IntegrasiEksternal extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'IntegrasiEksternal';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const STATUS_AKTIF = 'Aktif';

    public const STATUS_NONAKTIF = 'Nonaktif';

    public const STATUS_BERMASALAH = 'Bermasalah';

    /** @var list<string> */
    public const DAFTAR_STATUS = [self::STATUS_AKTIF, self::STATUS_NONAKTIF, self::STATUS_BERMASALAH];

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Jenis',
        'UrlDasar',
        'MetodeAutentikasi',
        'KonfigurasiTerenkripsi',
        'Status',
        'TerakhirSinkronPada',
    ];

    protected function casts(): array
    {
        return [
            'KonfigurasiTerenkripsi' => 'encrypted:array',
            'TerakhirSinkronPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return HasMany<PemetaanDataEksternal, $this>
     */
    public function pemetaan(): HasMany
    {
        return $this->hasMany(PemetaanDataEksternal::class, 'IntegrasiEksternalId', 'Id');
    }

    /**
     * @return HasMany<SinkronisasiEksternal, $this>
     */
    public function sinkronisasi(): HasMany
    {
        return $this->hasMany(SinkronisasiEksternal::class, 'IntegrasiEksternalId', 'Id');
    }
}
