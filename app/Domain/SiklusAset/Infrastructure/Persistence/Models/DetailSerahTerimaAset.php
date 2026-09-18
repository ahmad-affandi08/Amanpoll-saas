<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailSerahTerimaAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailSerahTerimaAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'SerahTerimaAsetId',
        'AsetId',
        'KondisiSaatDiserahkan',
        'KondisiSaatDiterima',
        'Catatan',
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

    public function serahTerimaAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset::class, 'SerahTerimaAsetId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

}
