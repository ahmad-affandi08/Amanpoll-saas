<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\EskalasiTingkatLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TingkatLayanan extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis;

    protected $table = 'TingkatLayanan';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Deskripsi',
        'HariKerja',
        'JamKerjaMulai',
        'JamKerjaSelesai',
        'MemperhitungkanHariLibur',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'HariKerja' => 'array',
            'MemperhitungkanHariLibur' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'SLA';
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return HasMany<AturanTingkatLayanan, $this> */
    public function aturan(): HasMany
    {
        return $this->hasMany(AturanTingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

    /** @return HasMany<EskalasiTingkatLayanan, $this> */
    public function eskalasi(): HasMany
    {
        return $this->hasMany(EskalasiTingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

    /** @return HasMany<KategoriKeluhan, $this> */
    public function kategoriKeluhan(): HasMany
    {
        return $this->hasMany(KategoriKeluhan::class, 'TingkatLayananId', 'Id');
    }
}
