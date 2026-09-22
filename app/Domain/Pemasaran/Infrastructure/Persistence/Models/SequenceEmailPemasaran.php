<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Rangkaian email yang dikirim bertahap (MARKETING.md 15). */
final class SequenceEmailPemasaran extends ModelDasar
{
    protected $table = 'SequenceEmailPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = ['Kode', 'Nama', 'Keterangan', 'Aktif'];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'Kode';
    }

    /** @return HasMany<LangkahSequenceEmail, $this> */
    public function langkah(): HasMany
    {
        return $this->hasMany(LangkahSequenceEmail::class, 'SequenceEmailPemasaranId', 'Id')
            ->orderBy('Urutan');
    }

    /** @return HasMany<PendaftaranSequence, $this> */
    public function pendaftaran(): HasMany
    {
        return $this->hasMany(PendaftaranSequence::class, 'SequenceEmailPemasaranId', 'Id');
    }
}
