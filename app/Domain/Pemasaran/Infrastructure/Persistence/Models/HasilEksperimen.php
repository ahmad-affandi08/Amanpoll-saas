<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\MetrikEksperimen;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Angka satu metrik untuk satu varian, lengkap dengan pembilang dan penyebutnya (MARKETING.md 22). */
final class HasilEksperimen extends ModelDasar
{
    protected $table = 'HasilEksperimen';

    public $timestamps = false;

    protected $fillable = [
        'EksperimenPemasaranId',
        'VarianEksperimenId',
        'Metrik',
        'Penyebut',
        'Pembilang',
        'Rasio',
        'DihitungPada',
    ];

    protected function casts(): array
    {
        return [
            'Metrik' => MetrikEksperimen::class,
            'Penyebut' => 'integer',
            'Pembilang' => 'integer',
            'Rasio' => 'decimal:4',
            'DihitungPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<VarianEksperimen, $this> */
    public function varian(): BelongsTo
    {
        return $this->belongsTo(VarianEksperimen::class, 'VarianEksperimenId', 'Id');
    }
}
