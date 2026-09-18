<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KategoriPenyedia extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KategoriPenyedia';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

}
