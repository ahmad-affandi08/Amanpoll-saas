<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
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

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<SerahTerimaAset, $this>
     */
    public function serahTerimaAset(): BelongsTo
    {
        return $this->belongsTo(SerahTerimaAset::class, 'SerahTerimaAsetId', 'Id');
    }

    /**
     * @return BelongsTo<Aset, $this>
     */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }
}
