<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\HasilLangkahOtomasi;
use App\Domain\Pemasaran\Domain\Enums\JenisLangkahOtomasi;
use App\Shared\Infrastructure\Persistence\HanyaTambah;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jejak satu langkah yang sudah dijalankan (MARKETING.md 17). */
final class LogEksekusiOtomasi extends ModelDasar
{
    use HanyaTambah;

    protected $table = 'LogEksekusiOtomasi';

    public $timestamps = false;

    protected $fillable = [
        'EksekusiOtomasiPemasaranId',
        'LangkahOtomasiPemasaranId',
        'Urutan',
        'Jenis',
        'Hasil',
        'Ringkasan',
        'TerjadiPada',
    ];

    protected function casts(): array
    {
        return [
            'Urutan' => 'integer',
            'Jenis' => JenisLangkahOtomasi::class,
            'Hasil' => HasilLangkahOtomasi::class,
            'TerjadiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<EksekusiOtomasiPemasaran, $this> */
    public function eksekusi(): BelongsTo
    {
        return $this->belongsTo(EksekusiOtomasiPemasaran::class, 'EksekusiOtomasiPemasaranId', 'Id');
    }
}
