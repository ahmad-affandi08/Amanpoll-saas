<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Pemasaran\Domain\Enums\ChannelKampanye;
use App\Domain\Pemasaran\Domain\Enums\ObjectiveKampanye;
use App\Domain\Pemasaran\Domain\Enums\StatusKampanye;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kampanye pemasaran (MARKETING.md 13). */
final class Kampanye extends ModelDasar
{
    use PunyaKodeOtomatis;

    protected $table = 'Kampanye';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'Objective',
        'Budget',
        'Audience',
        'Offer',
        'HalamanId',
        'FormulirId',
        'UtmSource',
        'UtmMedium',
        'UtmTerm',
        'UtmContent',
        'Status',
        'MulaiPada',
        'SelesaiPada',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'Objective' => ObjectiveKampanye::class,
            'Status' => StatusKampanye::class,
            'Budget' => 'decimal:2',
            'MulaiPada' => 'immutable_date',
            'SelesaiPada' => 'immutable_date',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'KMP';
    }

    /** @return HasMany<KampanyeChannel, $this> */
    public function channel(): HasMany
    {
        return $this->hasMany(KampanyeChannel::class, 'KampanyeId', 'Id');
    }

    /** @return HasMany<KampanyeBiaya, $this> */
    public function biaya(): HasMany
    {
        return $this->hasMany(KampanyeBiaya::class, 'KampanyeId', 'Id');
    }

    /** @return HasMany<KampanyeTarget, $this> */
    public function target(): HasMany
    {
        return $this->hasMany(KampanyeTarget::class, 'KampanyeId', 'Id');
    }

    /** @return HasMany<KampanyeKonten, $this> */
    public function konten(): HasMany
    {
        return $this->hasMany(KampanyeKonten::class, 'KampanyeId', 'Id');
    }

    /** @return BelongsTo<HalamanPemasaran, $this> */
    public function halaman(): BelongsTo
    {
        return $this->belongsTo(HalamanPemasaran::class, 'HalamanId', 'Id');
    }

    /** @return BelongsTo<FormulirPemasaran, $this> */
    public function formulir(): BelongsTo
    {
        return $this->belongsTo(FormulirPemasaran::class, 'FormulirId', 'Id');
    }

    /** @return list<ChannelKampanye> */
    public function daftarChannel(): array
    {
        return array_values(array_filter(array_map(
            fn (KampanyeChannel $satu): ?ChannelKampanye => ChannelKampanye::tryFrom($satu->Channel),
            $this->channel->all(),
        )));
    }

    /** Channel tunggal kampanye ini, atau null bila ia berjalan di lebih dari satu channel. */
    public function channelTunggal(): ?ChannelKampanye
    {
        $daftar = $this->daftarChannel();

        return count($daftar) === 1 ? $daftar[0] : null;
    }
}
