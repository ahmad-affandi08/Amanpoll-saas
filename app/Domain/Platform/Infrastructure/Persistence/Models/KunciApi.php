<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KunciApi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KunciApi';

    public $timestamps = false;

    /**
     * Hash kunci tidak pernah ikut serialisasi, supaya tidak bocor lewat
     * response JSON atau prop Inertia yang dibuat belakangan.
     *
     * @var list<string>
     */
    protected $hidden = ['HashKunci'];

    protected $fillable = [
        'OrganisasiId',
        'Nama',
        'AwalanKunci',
        'HashKunci',
        'Cakupan',
        'AlamatIpDiizinkan',
        'KadaluarsaPada',
        'TerakhirDipakaiPada',
        'Status',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'Cakupan' => 'array',
            'AlamatIpDiizinkan' => 'array',
            'KadaluarsaPada' => 'immutable_datetime',
            'TerakhirDipakaiPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
    }
}
