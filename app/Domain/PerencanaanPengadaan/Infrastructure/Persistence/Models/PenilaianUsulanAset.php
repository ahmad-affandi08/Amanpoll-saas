<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenilaianUsulanAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PenilaianUsulanAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'UsulanAsetId',
        'Kriteria',
        'Bobot',
        'Nilai',
        'Skor',
        'DinilaiOleh',
        'DinilaiPada',
    ];

    protected function casts(): array
    {
        return [
            'Bobot' => 'decimal:4',
            'Nilai' => 'decimal:4',
            'Skor' => 'decimal:4',
            'DinilaiPada' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<UsulanAset, $this>
     */
    public function usulanAset(): BelongsTo
    {
        return $this->belongsTo(UsulanAset::class, 'UsulanAsetId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function dinilaiOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DinilaiOleh', 'Id');
    }
}
