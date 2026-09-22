<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\TingkatAlertPemasaran;
use App\Shared\Infrastructure\Persistence\ModelDasar;

/** Satu alert growth tingkat platform (MARKETING.md 5). */
final class AlertPemasaran extends ModelDasar
{
    protected $table = 'AlertPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public $timestamps = false;

    protected $fillable = [
        'Kode',
        'Tingkat',
        'Judul',
        'Isi',
        'Rincian',
        'Tanggal',
        'DiselesaikanPada',
        'DibuatPada',
    ];

    protected function casts(): array
    {
        return [
            'Tingkat' => TingkatAlertPemasaran::class,
            'Rincian' => 'array',
            'Tanggal' => 'immutable_date',
            'DiselesaikanPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function terbuka(): bool
    {
        return $this->DiselesaikanPada === null;
    }
}
