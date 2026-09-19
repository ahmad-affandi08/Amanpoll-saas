<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Penyedia extends ModelDasar
{
    use SoftDeletes, MilikOrganisasi;

    protected $table = 'Penyedia';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';
    public const DELETED_AT = 'DihapusPada';

    public const STATUS_AKTIF = 'Aktif';
    public const STATUS_NONAKTIF = 'Nonaktif';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'NamaLegal',
        'NomorIdentitasPajak',
        'Email',
        'Telepon',
        'Website',
        'Alamat',
        'Kota',
        'Provinsi',
        'Negara',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return HasMany<KontakPenyedia, $this>
     */
    public function kontakPenyedia(): HasMany
    {
        return $this->hasMany(KontakPenyedia::class, 'PenyediaId', 'Id');
    }

    /**
     * @return HasMany<PenilaianPenyedia, $this>
     */
    public function penilaianPenyedia(): HasMany
    {
        return $this->hasMany(PenilaianPenyedia::class, 'PenyediaId', 'Id')->orderByDesc('PeriodeMulai');
    }

    /**
     * @return HasMany<PenyediaKategori, $this>
     */
    public function penyediaKategori(): HasMany
    {
        return $this->hasMany(PenyediaKategori::class, 'PenyediaId', 'Id');
    }

    /**
     * @return BelongsToMany<KategoriPenyedia, $this>
     */
    public function kategoriPenyedia(): BelongsToMany
    {
        return $this->belongsToMany(KategoriPenyedia::class, 'PenyediaKategori', 'PenyediaId', 'KategoriPenyediaId')
            ->withPivot('Id');
    }

}
