<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PersyaratanKepatuhan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PersyaratanKepatuhan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'StandarKepatuhanId',
        'Kode',
        'Nama',
        'Deskripsi',
        'BuktiYangDiperlukan',
        'IntervalHari',
    ];

    protected function casts(): array
    {
        return [
            'IntervalHari' => 'integer',
            'DibuatPada' => 'immutable_datetime',
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
     * @return BelongsTo<StandarKepatuhan, $this>
     */
    public function standarKepatuhan(): BelongsTo
    {
        return $this->belongsTo(StandarKepatuhan::class, 'StandarKepatuhanId', 'Id');
    }

    /**
     * @return HasMany<KepatuhanAset, $this>
     */
    public function kepatuhanAset(): HasMany
    {
        return $this->hasMany(KepatuhanAset::class, 'PersyaratanKepatuhanId', 'Id');
    }
}
