<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Kode yang dipakai satu pelanggan untuk mengajak orang lain (MARKETING.md 20). */
final class KodeReferral extends ModelDasar
{
    protected $table = 'KodeReferral';

    public const CREATED_AT = 'DibuatPada';

    public $timestamps = false;

    protected $fillable = ['ProgramReferralId', 'OrganisasiId', 'Kode', 'Aktif', 'DibuatPada'];

    protected function casts(): array
    {
        return ['Aktif' => 'boolean', 'DibuatPada' => 'immutable_datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'Kode';
    }

    /** @return BelongsTo<ProgramReferral, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ProgramReferral::class, 'ProgramReferralId', 'Id');
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }
}
