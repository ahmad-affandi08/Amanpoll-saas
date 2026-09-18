<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StandarKepatuhan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'StandarKepatuhan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Penerbit',
        'VersiStandar',
        'JenisIndustri',
        'Deskripsi',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

}
