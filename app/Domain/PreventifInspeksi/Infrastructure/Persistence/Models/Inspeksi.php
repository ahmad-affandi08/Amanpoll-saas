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

final class Inspeksi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'Inspeksi';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'TemplatInspeksiId',
        'AsetId',
        'PelaksanaanDaftarPeriksaId',
        'DijadwalkanPada',
        'DilaksanakanPada',
        'Status',
        'Hasil',
        'Temuan',
        'TindakLanjut',
        'PerintahKerjaId',
        'DilaksanakanOleh',
    ];

    protected function casts(): array
    {
        return [
            'DijadwalkanPada' => 'immutable_datetime',
            'DilaksanakanPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<TemplatInspeksi, $this> */
    public function templatInspeksi(): BelongsTo
    {
        return $this->belongsTo(TemplatInspeksi::class, 'TemplatInspeksiId', 'Id');
    }

    /** @return BelongsTo<Aset, $this> */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    /** @return BelongsTo<PelaksanaanDaftarPeriksa, $this> */
    public function pelaksanaanDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(PelaksanaanDaftarPeriksa::class, 'PelaksanaanDaftarPeriksaId', 'Id');
    }

    /** @return BelongsTo<PerintahKerja, $this> */
    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function dilaksanakanOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DilaksanakanOleh', 'Id');
    }
}
