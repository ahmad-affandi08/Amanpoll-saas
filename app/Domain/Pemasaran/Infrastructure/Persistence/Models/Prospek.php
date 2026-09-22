<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu prospek (MARKETING.md 5.2). */
final class Prospek extends ModelDasar
{
    protected $table = 'Prospek';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiProspekId',
        'PengenalPengunjung',
        'OrganisasiId',
        'Nama',
        'Email',
        'Telepon',
        'WhatsApp',
        'Jabatan',
        'Sumber',
        'KampanyeId',
        'TahapPipelineId',
        'Skor',
        'Catatan',
        'AktivitasTerakhirPada',
    ];

    protected function casts(): array
    {
        return [
            'Skor' => 'integer',
            'AktivitasTerakhirPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<OrganisasiProspek, $this> */
    public function organisasiProspek(): BelongsTo
    {
        return $this->belongsTo(OrganisasiProspek::class, 'OrganisasiProspekId', 'Id');
    }

    /** @return BelongsTo<TahapPipeline, $this> */
    public function tahap(): BelongsTo
    {
        return $this->belongsTo(TahapPipeline::class, 'TahapPipelineId', 'Id');
    }

    /** @return BelongsTo<Kampanye, $this> */
    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(Kampanye::class, 'KampanyeId', 'Id');
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return HasMany<KontakProspek, $this> */
    public function kontak(): HasMany
    {
        return $this->hasMany(KontakProspek::class, 'ProspekId', 'Id');
    }

    /** @return HasMany<AktivitasProspek, $this> */
    public function aktivitas(): HasMany
    {
        return $this->hasMany(AktivitasProspek::class, 'ProspekId', 'Id');
    }

    /** @return HasMany<RiwayatTahapProspek, $this> */
    public function riwayatTahap(): HasMany
    {
        return $this->hasMany(RiwayatTahapProspek::class, 'ProspekId', 'Id');
    }

    /** @return HasMany<SkorProspek, $this> */
    public function rincianSkor(): HasMany
    {
        return $this->hasMany(SkorProspek::class, 'ProspekId', 'Id');
    }

    /** @return BelongsToMany<TagProspek, $this> */
    public function tag(): BelongsToMany
    {
        return $this->belongsToMany(TagProspek::class, 'ProspekTag', 'ProspekId', 'TagProspekId')
            ->withPivot('Id');
    }
}
