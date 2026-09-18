<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KategoriSukuCadang extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KategoriSukuCadang';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'IndukId',
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

    public function induk(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang::class, 'IndukId', 'Id');
    }

}
