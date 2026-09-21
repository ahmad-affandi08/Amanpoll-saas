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

    /**
     * Cast `encrypted` melindungi data saat tersimpan, bukan saat diserialisasi:
     * `toArray()` mengembalikan nilai yang sudah didekripsi. Kredensial penyedia
     * karena itu disembunyikan eksplisit.
     *
     * @var list<string>
     */
    protected $hidden = ['KonfigurasiTerenkripsi'];

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

    /** @return BelongsTo<Organisasi, $this> */
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
