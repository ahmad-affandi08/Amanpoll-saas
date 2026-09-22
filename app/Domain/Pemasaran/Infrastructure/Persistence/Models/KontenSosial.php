<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu konten utama yang disebarkan ke banyak channel (MARKETING.md 18). */
final class KontenSosial extends ModelDasar
{
    protected $table = 'KontenSosial';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Judul',
        'Ringkasan',
        'MediaUrl',
        'HalamanId',
        'KampanyeId',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<DistribusiKontenSosial, $this> */
    public function distribusi(): HasMany
    {
        return $this->hasMany(DistribusiKontenSosial::class, 'KontenSosialId', 'Id');
    }

    /** @return BelongsTo<Kampanye, $this> */
    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(Kampanye::class, 'KampanyeId', 'Id');
    }

    /** @return BelongsTo<HalamanPemasaran, $this> */
    public function halaman(): BelongsTo
    {
        return $this->belongsTo(HalamanPemasaran::class, 'HalamanId', 'Id');
    }
}
