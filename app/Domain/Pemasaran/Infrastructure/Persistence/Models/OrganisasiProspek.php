<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Perusahaan yang diwakili satu atau beberapa prospek (MARKETING.md 24). */
final class OrganisasiProspek extends ModelDasar
{
    protected $table = 'OrganisasiProspek';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Nama',
        'Industri',
        'JumlahLokasi',
        'EstimasiAset',
        'EstimasiTeknisi',
        'Kota',
        'Negara',
        'Situs',
    ];

    protected function casts(): array
    {
        return [
            'JumlahLokasi' => 'integer',
            'EstimasiAset' => 'integer',
            'EstimasiTeknisi' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<Prospek, $this> */
    public function prospek(): HasMany
    {
        return $this->hasMany(Prospek::class, 'OrganisasiProspekId', 'Id');
    }
}
