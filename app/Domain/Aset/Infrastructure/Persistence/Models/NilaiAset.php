<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NilaiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'NilaiAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'TanggalNilai',
        'NilaiBuku',
        'AkumulasiPenyusutan',
        'BebanPenyusutanPeriode',
        'Metode',
    ];

    protected function casts(): array
    {
        return [
            'TanggalNilai' => 'date',
            'NilaiBuku' => 'decimal:2',
            'AkumulasiPenyusutan' => 'decimal:2',
            'BebanPenyusutanPeriode' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
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
     * @return BelongsTo<Aset, $this>
     */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }
}
