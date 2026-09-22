<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu program partner beserta jendela atribusi dan aturan komisinya (MARKETING.md 21). */
final class ProgramPartner extends ModelDasar
{
    protected $table = 'ProgramPartner';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'Keterangan',
        'HariAtribusi',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'HariAtribusi' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'Kode';
    }

    /** @return HasMany<Partner, $this> */
    public function partner(): HasMany
    {
        return $this->hasMany(Partner::class, 'ProgramPartnerId', 'Id');
    }

    /** @return HasMany<AturanKomisiPartner, $this> */
    public function aturanKomisi(): HasMany
    {
        return $this->hasMany(AturanKomisiPartner::class, 'ProgramPartnerId', 'Id');
    }
}
