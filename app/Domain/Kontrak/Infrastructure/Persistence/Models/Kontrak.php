<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Kontrak extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'Kontrak';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'PenyediaId',
        'Nomor',
        'Nama',
        'Jenis',
        'MulaiPada',
        'BerakhirPada',
        'Nilai',
        'MataUang',
        'TingkatLayananId',
        'PeringatanHariSebelum',
        'Status',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'MulaiPada' => 'date',
            'BerakhirPada' => 'date',
            'Nilai' => 'decimal:2',
            'PeringatanHariSebelum' => 'integer',
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
     * @return BelongsTo<Penyedia, $this>
     */
    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class, 'PenyediaId', 'Id');
    }

    /**
     * @return BelongsTo<TingkatLayanan, $this>
     */
    public function tingkatLayanan(): BelongsTo
    {
        return $this->belongsTo(TingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

    /**
     * @return HasMany<KontrakAset, $this>
     */
    public function kontrakAset(): HasMany
    {
        return $this->hasMany(KontrakAset::class, 'KontrakId', 'Id');
    }

    /**
     * @return HasMany<LayananKontrak, $this>
     */
    public function layanan(): HasMany
    {
        return $this->hasMany(LayananKontrak::class, 'KontrakId', 'Id');
    }
}
