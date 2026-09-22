<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusEksekusiOtomasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu jalannya otomasi atas satu peristiwa (MARKETING.md 17). */
final class EksekusiOtomasiPemasaran extends ModelDasar
{
    protected $table = 'EksekusiOtomasiPemasaran';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const CREATED_AT = null;

    protected $fillable = [
        'OtomasiPemasaranId',
        'VersiOtomasiPemasaranId',
        'EventPemasaranId',
        'ProspekId',
        'OrganisasiId',
        'KunciIdempotensi',
        'Status',
        'LangkahBerikutnya',
        'Percobaan',
        'LanjutPada',
        'Galat',
        'DimulaiPada',
        'SelesaiPada',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusEksekusiOtomasi::class,
            'LangkahBerikutnya' => 'integer',
            'Percobaan' => 'integer',
            'LanjutPada' => 'immutable_datetime',
            'DimulaiPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<OtomasiPemasaran, $this> */
    public function otomasi(): BelongsTo
    {
        return $this->belongsTo(OtomasiPemasaran::class, 'OtomasiPemasaranId', 'Id');
    }

    /** @return BelongsTo<VersiOtomasiPemasaran, $this> */
    public function versi(): BelongsTo
    {
        return $this->belongsTo(VersiOtomasiPemasaran::class, 'VersiOtomasiPemasaranId', 'Id');
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }

    /** @return BelongsTo<EventPemasaran, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(EventPemasaran::class, 'EventPemasaranId', 'Id');
    }

    /** @return HasMany<LogEksekusiOtomasi, $this> */
    public function log(): HasMany
    {
        return $this->hasMany(LogEksekusiOtomasi::class, 'EksekusiOtomasiPemasaranId', 'Id')
            ->orderBy('Urutan');
    }
}
