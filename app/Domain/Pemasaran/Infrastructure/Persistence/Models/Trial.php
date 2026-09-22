<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Pemasaran\Domain\Enums\ButirAktivasi;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Perjalanan satu trial, dari pendaftaran sampai konversi (MARKETING.md 12). */
final class Trial extends ModelDasar
{
    protected $table = 'Trial';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'ProspekId',
        'LanggananId',
        'PengenalPengunjung',
        'Status',
        'MulaiPada',
        'BerakhirPada',
        'TeraktivasiPada',
        'KonversiPada',
        'HariPerpanjangan',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusTrial::class,
            'HariPerpanjangan' => 'integer',
            'MulaiPada' => 'immutable_datetime',
            'BerakhirPada' => 'immutable_datetime',
            'TeraktivasiPada' => 'immutable_datetime',
            'KonversiPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }

    /** @return BelongsTo<Langganan, $this> */
    public function langganan(): BelongsTo
    {
        return $this->belongsTo(Langganan::class, 'LanggananId', 'Id');
    }

    /** @return HasMany<ButirAktivasiTrial, $this> */
    public function butir(): HasMany
    {
        return $this->hasMany(ButirAktivasiTrial::class, 'TrialId', 'Id');
    }

    /**
     * Kode butir sebagai teks. Kolomnya di-cast menjadi enum, jadi pluck() saja
     * mengembalikan objek yang tidak pernah cocok dengan perbandingan ketat.
     *
     * @return list<string>
     */
    public function butirSelesai(): array
    {
        return array_values($this->butir
            ->map(fn (ButirAktivasiTrial $satu): string => $satu->Butir->value)
            ->all());
    }

    public function seluruhButirWajibSelesai(): bool
    {
        $selesai = $this->butirSelesai();

        foreach (ButirAktivasi::wajibUntukAktivasi() as $wajib) {
            if (! in_array($wajib->value, $selesai, true)) {
                return false;
            }
        }

        return true;
    }
}
