<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Penyedia extends ModelDasar
{
    use SoftDeletes, MilikOrganisasi;

    protected $table = 'Penyedia';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';
    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'NamaLegal',
        'NomorIdentitasPajak',
        'Email',
        'Telepon',
        'Website',
        'Alamat',
        'Kota',
        'Provinsi',
        'Negara',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

}
