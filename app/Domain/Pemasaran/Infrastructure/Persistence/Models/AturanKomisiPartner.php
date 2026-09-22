<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\JenisKomisiPartner;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Aturan komisi: bawaan program bila `PartnerId` kosong, khusus satu partner bila terisi (MARKETING.md 21). */
final class AturanKomisiPartner extends ModelDasar
{
    protected $table = 'AturanKomisiPartner';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'ProgramPartnerId',
        'PartnerId',
        'Nama',
        'Jenis',
        'Nilai',
        'MaksPembayaran',
        'Aktif',
        'BerlakuDari',
        'BerlakuSampai',
    ];

    protected function casts(): array
    {
        return [
            'Jenis' => JenisKomisiPartner::class,
            'Nilai' => 'decimal:2',
            'MaksPembayaran' => 'integer',
            'Aktif' => 'boolean',
            'BerlakuDari' => 'immutable_datetime',
            'BerlakuSampai' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function berlakuPada(CarbonImmutable $saat): bool
    {
        if (! $this->Aktif) {
            return false;
        }

        if ($this->BerlakuDari !== null && $saat->lessThan($this->BerlakuDari)) {
            return false;
        }

        return $this->BerlakuSampai === null || $saat->lessThanOrEqualTo($this->BerlakuSampai);
    }

    /** @return BelongsTo<ProgramPartner, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ProgramPartner::class, 'ProgramPartnerId', 'Id');
    }

    /** @return BelongsTo<Partner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'PartnerId', 'Id');
    }
}
