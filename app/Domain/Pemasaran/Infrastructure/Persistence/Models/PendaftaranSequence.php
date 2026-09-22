<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusPendaftaranSequence;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Keikutsertaan satu prospek dalam satu sequence (MARKETING.md 15). */
final class PendaftaranSequence extends ModelDasar
{
    protected $table = 'PendaftaranSequence';

    public $timestamps = false;

    protected $fillable = [
        'SequenceEmailPemasaranId',
        'ProspekId',
        'Status',
        'DimulaiPada',
        'SelesaiPada',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusPendaftaranSequence::class,
            'DimulaiPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<SequenceEmailPemasaran, $this> */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(SequenceEmailPemasaran::class, 'SequenceEmailPemasaranId', 'Id');
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }

    /** @return HasMany<PengirimanEmailPemasaran, $this> */
    public function pengiriman(): HasMany
    {
        return $this->hasMany(PengirimanEmailPemasaran::class, 'PendaftaranSequenceId', 'Id');
    }
}
