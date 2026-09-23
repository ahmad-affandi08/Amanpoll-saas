<?php

declare(strict_types=1);

namespace App\Domain\Kodefikasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Kodefikasi\Domain\Enums\StandarKodefikasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu baris referensi kodefikasi barang pemerintah.
 *
 * Katalognya diimpor dari daftar resmi, bukan disusun sendiri: kodenya kunci
 * yang dikenal Kemenkeu atau Kemendagri, jadi tidak boleh diterbitkan otomatis
 * seperti kode data induk kita yang lain.
 */
final class KodeBarang extends ModelDasar
{
    use MilikOrganisasi, SoftDeletes;

    protected $table = 'KodeBarang';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'Standar',
        'Kode',
        'Uraian',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Standar' => StandarKodefikasi::class,
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<KodeBarangAset, $this> */
    public function penetapan(): HasMany
    {
        return $this->hasMany(KodeBarangAset::class, 'KodeBarangId', 'Id');
    }
}
