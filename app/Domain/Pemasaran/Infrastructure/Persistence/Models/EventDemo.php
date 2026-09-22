<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\JenisEventDemo;
use App\Shared\Infrastructure\Persistence\HanyaTambah;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu peristiwa di dalam sesi demo (MARKETING.md 11). */
final class EventDemo extends ModelDasar
{
    use HanyaTambah;

    protected $table = 'EventDemo';

    public $timestamps = false;

    protected $fillable = [
        'SesiDemoId',
        'Jenis',
        'Modul',
        'Rincian',
        'TerjadiPada',
    ];

    protected function casts(): array
    {
        return [
            'Jenis' => JenisEventDemo::class,
            'Rincian' => 'array',
            'TerjadiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<SesiDemo, $this> */
    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiDemo::class, 'SesiDemoId', 'Id');
    }
}
