<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Gudang extends ModelDasar
{
    use MilikOrganisasi;

    public const STATUS_AKTIF = 'Aktif';

    public const STATUS_NONAKTIF = 'Nonaktif';

    protected $table = 'Gudang';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'LokasiId',
        'Kode',
        'Nama',
        'PenanggungJawabId',
        'Status',
    ];

    protected function casts(): array
    {
        return [
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
     * @return BelongsTo<Lokasi, $this>
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'LokasiId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PenanggungJawabId', 'Id');
    }

    /**
     * @return HasMany<LokasiGudang, $this>
     */
    public function lokasiGudang(): HasMany
    {
        return $this->hasMany(LokasiGudang::class, 'GudangId', 'Id');
    }
}
