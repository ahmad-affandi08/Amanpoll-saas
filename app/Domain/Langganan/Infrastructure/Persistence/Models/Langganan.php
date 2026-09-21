<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Langganan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'Langganan';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'PaketLanggananId',
        'Siklus',
        'MulaiPada',
        'BerakhirPada',
        'UjiCobaSampai',
        'Status',
        'BatalPada',
    ];

    protected function casts(): array
    {
        return [
            'MulaiPada' => 'immutable_date',
            'BerakhirPada' => 'immutable_date',
            'UjiCobaSampai' => 'immutable_date',
            'BatalPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<PaketLangganan, $this> */
    public function paketLangganan(): BelongsTo
    {
        return $this->belongsTo(PaketLangganan::class, 'PaketLanggananId', 'Id');
    }

    /** @return HasMany<TagihanLangganan, $this> */
    public function tagihan(): HasMany
    {
        return $this->hasMany(TagihanLangganan::class, 'LanggananId', 'Id');
    }
}
