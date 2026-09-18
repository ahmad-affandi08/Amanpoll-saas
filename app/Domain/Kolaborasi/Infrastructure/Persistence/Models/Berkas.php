<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Berkas extends ModelDasar
{
    use SoftDeletes, MilikOrganisasi;

    protected $table = 'Berkas';

    public $timestamps = false;
    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'NamaAsli',
        'NamaPenyimpanan',
        'MediaPenyimpanan',
        'LokasiPenyimpanan',
        'JenisMime',
        'UkuranByte',
        'HashSha256',
        'DataTambahan',
        'DiunggahOleh',
    ];

    protected function casts(): array
    {
        return [
            'UkuranByte' => 'integer',
            'DataTambahan' => 'array',
            'DibuatPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function diunggahOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DiunggahOleh', 'Id');
    }

}
