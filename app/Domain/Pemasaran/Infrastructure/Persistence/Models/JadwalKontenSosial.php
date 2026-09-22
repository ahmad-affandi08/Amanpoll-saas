<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusJadwalSosial;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu rencana penerbitan; penjadwalan ulang menulis baris baru, bukan menimpa yang lama (MARKETING.md 18). */
final class JadwalKontenSosial extends ModelDasar
{
    protected $table = 'JadwalKontenSosial';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'DistribusiKontenSosialId',
        'JadwalPada',
        'Status',
        'DijalankanPada',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusJadwalSosial::class,
            'JadwalPada' => 'immutable_datetime',
            'DijalankanPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<DistribusiKontenSosial, $this> */
    public function distribusi(): BelongsTo
    {
        return $this->belongsTo(DistribusiKontenSosial::class, 'DistribusiKontenSosialId', 'Id');
    }
}
