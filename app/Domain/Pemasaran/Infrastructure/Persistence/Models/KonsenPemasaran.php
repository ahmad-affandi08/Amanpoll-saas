<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Domain\Pemasaran\Domain\Enums\SumberKonsen;
use App\Shared\Infrastructure\Persistence\HanyaTambah;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu pencatatan persetujuan atau pencabutannya (MARKETING.md 27). */
final class KonsenPemasaran extends ModelDasar
{
    use HanyaTambah;

    protected $table = 'KonsenPemasaran';

    public $timestamps = false;

    protected $fillable = [
        'ProspekId',
        'Kanal',
        'Kontak',
        'Diberikan',
        'Sumber',
        'VersiKebijakan',
        'AlamatIp',
        'AgenPengguna',
        'DicatatPada',
    ];

    protected function casts(): array
    {
        return [
            'Diberikan' => 'boolean',
            'Kanal' => KanalPesan::class,
            'Sumber' => SumberKonsen::class,
            'DicatatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }
}
