<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\JenisLangkahOtomasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu langkah dalam satu versi otomasi (MARKETING.md 17). */
final class LangkahOtomasiPemasaran extends ModelDasar
{
    protected $table = 'LangkahOtomasiPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public $timestamps = false;

    protected $fillable = ['VersiOtomasiPemasaranId', 'Urutan', 'Jenis', 'Konfigurasi', 'DibuatPada'];

    protected function casts(): array
    {
        return [
            'Urutan' => 'integer',
            'Jenis' => JenisLangkahOtomasi::class,
            'Konfigurasi' => 'array',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<VersiOtomasiPemasaran, $this> */
    public function versi(): BelongsTo
    {
        return $this->belongsTo(VersiOtomasiPemasaran::class, 'VersiOtomasiPemasaranId', 'Id');
    }
}
