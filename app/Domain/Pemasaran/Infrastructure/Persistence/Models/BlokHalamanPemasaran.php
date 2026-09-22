<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Shared\Infrastructure\Persistence\HanyaTambah;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu blok pada satu versi halaman (MARKETING.md 8). */
final class BlokHalamanPemasaran extends ModelDasar
{
    use HanyaTambah;

    protected $table = 'BlokHalamanPemasaran';

    public $timestamps = false;

    protected $fillable = [
        'VersiHalamanPemasaranId',
        'Jenis',
        'Urutan',
        'Isi',
        'FormulirPemasaranId',
    ];

    protected function casts(): array
    {
        return [
            'Jenis' => JenisBlokHalaman::class,
            'Urutan' => 'integer',
            'Isi' => 'array',
        ];
    }

    /** @return BelongsTo<VersiHalamanPemasaran, $this> */
    public function versi(): BelongsTo
    {
        return $this->belongsTo(VersiHalamanPemasaran::class, 'VersiHalamanPemasaranId', 'Id');
    }

    /** @return BelongsTo<FormulirPemasaran, $this> */
    public function formulir(): BelongsTo
    {
        return $this->belongsTo(FormulirPemasaran::class, 'FormulirPemasaranId', 'Id');
    }
}
