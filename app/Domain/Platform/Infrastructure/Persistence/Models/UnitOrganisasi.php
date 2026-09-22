<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class UnitOrganisasi extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis, SoftDeletes;

    protected $table = 'UnitOrganisasi';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'IndukId',
        'Kode',
        'Nama',
        'Jenis',
        'Email',
        'Telepon',
        'Status',
        'Urutan',
    ];

    protected function casts(): array
    {
        return [
            'Urutan' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'UNT';
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<UnitOrganisasi, $this> */
    public function induk(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'IndukId', 'Id');
    }
}
