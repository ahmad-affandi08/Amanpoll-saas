<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu baris nomenklatur alat kesehatan ASPAK.
 *
 * Katalognya diimpor dari ASPAK, bukan disusun sendiri: `Kode` adalah kunci
 * yang dikenal Kemenkes, jadi tidak boleh diterbitkan otomatis seperti kode
 * data induk kita yang lain.
 */
final class AlkesAspak extends ModelDasar
{
    use MilikOrganisasi, SoftDeletes;

    protected $table = 'AlkesAspak';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Kelompok',
        'Satuan',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<PemetaanAspak, $this> */
    public function pemetaan(): HasMany
    {
        return $this->hasMany(PemetaanAspak::class, 'AlkesAspakId', 'Id');
    }
}
