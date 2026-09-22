<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Corong dan revenue satu kampanye pada satu hari, dihitung di muka (MARKETING.md 5). */
final class MetrikKampanye extends ModelDasar
{
    protected $table = 'MetrikKampanye';

    public $timestamps = false;

    protected $fillable = [
        'KampanyeId',
        'Channel',
        'Tanggal',
        'Visitor',
        'Lead',
        'Trial',
        'Teraktivasi',
        'Bayar',
        'Revenue',
        'Biaya',
        'DihitungPada',
    ];

    protected function casts(): array
    {
        return [
            'Tanggal' => 'immutable_date',
            'Visitor' => 'integer',
            'Lead' => 'integer',
            'Trial' => 'integer',
            'Teraktivasi' => 'integer',
            'Bayar' => 'integer',
            'Revenue' => 'decimal:2',
            'Biaya' => 'decimal:2',
            'DihitungPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Kampanye, $this> */
    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(Kampanye::class, 'KampanyeId', 'Id');
    }
}
