<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\JenisFieldFormulir;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu field pada satu formulir (MARKETING.md 10). */
final class FieldFormulirPemasaran extends ModelDasar
{
    protected $table = 'FieldFormulirPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'FormulirPemasaranId',
        'Kode',
        'Label',
        'Jenis',
        'Wajib',
        'Urutan',
        'Pilihan',
        'Placeholder',
        'Bantuan',
    ];

    protected function casts(): array
    {
        return [
            'Jenis' => JenisFieldFormulir::class,
            'Wajib' => 'boolean',
            'Urutan' => 'integer',
            'Pilihan' => 'array',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<FormulirPemasaran, $this> */
    public function formulir(): BelongsTo
    {
        return $this->belongsTo(FormulirPemasaran::class, 'FormulirPemasaranId', 'Id');
    }
}
