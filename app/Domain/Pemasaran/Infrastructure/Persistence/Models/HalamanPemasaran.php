<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Identitas satu halaman pemasaran (MARKETING.md 8).
 *
 * Isinya ada di versi; baris ini hanya menunjuk versi mana yang terbit dan mana
 * yang sedang disunting.
 */
final class HalamanPemasaran extends ModelDasar
{
    protected $table = 'HalamanPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Slug',
        'Tipe',
        'Judul',
        'Status',
        'Segmen',
        'KampanyeId',
        'VersiTerbitId',
        'VersiDrafId',
        'TerbitPada',
        'TarikPada',
        'NoIndex',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusHalamanPemasaran::class,
            'NoIndex' => 'boolean',
            'TerbitPada' => 'immutable_datetime',
            'TarikPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<VersiHalamanPemasaran, $this> */
    public function versi(): HasMany
    {
        return $this->hasMany(VersiHalamanPemasaran::class, 'HalamanPemasaranId', 'Id');
    }

    /** @return BelongsTo<VersiHalamanPemasaran, $this> */
    public function versiTerbit(): BelongsTo
    {
        return $this->belongsTo(VersiHalamanPemasaran::class, 'VersiTerbitId', 'Id');
    }

    /** @return BelongsTo<VersiHalamanPemasaran, $this> */
    public function versiDraf(): BelongsTo
    {
        return $this->belongsTo(VersiHalamanPemasaran::class, 'VersiDrafId', 'Id');
    }

    /** @return BelongsTo<Kampanye, $this> */
    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(Kampanye::class, 'KampanyeId', 'Id');
    }

    /**
     * Terlihat publik hanya bila statusnya Terbit dan ada versi yang ditunjuk.
     * Status saja tidak cukup: halaman yang diterbitkan sebelum versinya ada
     * akan merender badan kosong.
     */
    public function terbit(): bool
    {
        return $this->Status->terlihatPublik() && $this->VersiTerbitId !== null;
    }
}
