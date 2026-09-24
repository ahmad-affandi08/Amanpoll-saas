<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Platform\Domain\Enums\ModeLapangan;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Peran extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis, SoftDeletes;

    protected $table = 'Peran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Keterangan',
        'BawaanSistem',
        'TampilanLapangan',
    ];

    protected function casts(): array
    {
        return [
            'BawaanSistem' => 'boolean',
            'TampilanLapangan' => ModeLapangan::class,
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'PRN';
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return HasMany<PenggunaPeran, $this>
     */
    public function penggunaPeran(): HasMany
    {
        return $this->hasMany(PenggunaPeran::class, 'PeranId', 'Id');
    }

    /**
     * @return HasMany<PeranIzin, $this>
     */
    public function peranIzin(): HasMany
    {
        return $this->hasMany(PeranIzin::class, 'PeranId', 'Id');
    }
}
