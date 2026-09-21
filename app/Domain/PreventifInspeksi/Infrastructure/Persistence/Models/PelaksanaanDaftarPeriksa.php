<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PelaksanaanDaftarPeriksa extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PelaksanaanDaftarPeriksa';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'TemplatDaftarPeriksaId',
        'PerintahKerjaId',
        'AsetId',
        'DilaksanakanOleh',
        'MulaiPada',
        'SelesaiPada',
        'Status',
        'Skor',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'MulaiPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
            'Skor' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<TemplatDaftarPeriksa, $this> */
    public function templatDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(TemplatDaftarPeriksa::class, 'TemplatDaftarPeriksaId', 'Id');
    }

    /** @return BelongsTo<PerintahKerja, $this> */
    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    /** @return BelongsTo<Aset, $this> */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function dilaksanakanOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DilaksanakanOleh', 'Id');
    }

    /** @return HasMany<JawabanDaftarPeriksa, $this> */
    public function jawaban(): HasMany
    {
        return $this->hasMany(JawabanDaftarPeriksa::class, 'PelaksanaanDaftarPeriksaId', 'Id');
    }
}
