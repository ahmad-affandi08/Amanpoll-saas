<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Penetapan satu pengunjung ke satu varian; barisnya tidak pernah berpindah (MARKETING.md 22). */
final class PartisipasiEksperimen extends ModelDasar
{
    protected $table = 'PartisipasiEksperimen';

    public $timestamps = false;

    protected $fillable = [
        'EksperimenPemasaranId',
        'VarianEksperimenId',
        'PengenalPengunjung',
        'DitetapkanPada',
    ];

    protected function casts(): array
    {
        return ['DitetapkanPada' => 'immutable_datetime'];
    }

    /** @return BelongsTo<VarianEksperimen, $this> */
    public function varian(): BelongsTo
    {
        return $this->belongsTo(VarianEksperimen::class, 'VarianEksperimenId', 'Id');
    }

    /** @return BelongsTo<EksperimenPemasaran, $this> */
    public function eksperimen(): BelongsTo
    {
        return $this->belongsTo(EksperimenPemasaran::class, 'EksperimenPemasaranId', 'Id');
    }
}
