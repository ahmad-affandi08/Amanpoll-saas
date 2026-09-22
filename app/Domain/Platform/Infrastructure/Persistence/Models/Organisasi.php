<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Organisasi extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'Organisasi';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'NamaLegal',
        'JenisUsaha',
        'NomorIdentitasPajak',
        'Email',
        'Telepon',
        'Alamat',
        'Negara',
        'Provinsi',
        'Kota',
        'ZonaWaktu',
        'LogoUrl',
        'Status',
        'Demo',
    ];

    protected function casts(): array
    {
        return [
            'Demo' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }
}
