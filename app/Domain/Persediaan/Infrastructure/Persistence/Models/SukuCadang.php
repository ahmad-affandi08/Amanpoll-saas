<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SukuCadang extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis, SoftDeletes;

    protected $table = 'SukuCadang';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'KategoriSukuCadangId',
        'Kode',
        'Nama',
        'NomorBagian',
        'KodeBatang',
        'SatuanDasar',
        'StokMinimum',
        'StokMaksimum',
        'TitikPesanUlang',
        'HargaRataRata',
        'MemakaiBatch',
        'MemakaiKadaluarsa',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'StokMinimum' => 'decimal:4',
            'StokMaksimum' => 'decimal:4',
            'TitikPesanUlang' => 'decimal:4',
            'HargaRataRata' => 'decimal:2',
            'MemakaiBatch' => 'boolean',
            'MemakaiKadaluarsa' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'SPR';
    }

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<KategoriSukuCadang, $this>
     */
    public function kategoriSukuCadang(): BelongsTo
    {
        return $this->belongsTo(KategoriSukuCadang::class, 'KategoriSukuCadangId', 'Id');
    }

    /**
     * @return HasMany<KompatibilitasSukuCadang, $this>
     */
    public function kompatibilitasSukuCadang(): HasMany
    {
        return $this->hasMany(KompatibilitasSukuCadang::class, 'SukuCadangId', 'Id');
    }

    /**
     * @return HasMany<StokSukuCadang, $this>
     */
    public function stok(): HasMany
    {
        return $this->hasMany(StokSukuCadang::class, 'SukuCadangId', 'Id');
    }

    /**
     * @return HasMany<PemakaianSukuCadang, $this>
     */
    public function pemakaian(): HasMany
    {
        return $this->hasMany(PemakaianSukuCadang::class, 'SukuCadangId', 'Id')->latest('DipakaiPada');
    }

    /**
     * @return HasMany<ReservasiSukuCadang, $this>
     */
    public function reservasi(): HasMany
    {
        return $this->hasMany(ReservasiSukuCadang::class, 'SukuCadangId', 'Id')->latest('DibuatPada');
    }
}
