<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HasilTitikUkurKalibrasi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'HasilTitikUkurKalibrasi';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PelaksanaanKalibrasiId',
        'TitikUkurKalibrasiId',
        'NamaTitik',
        'NilaiReferensi',
        'NilaiTerukur',
        'Koreksi',
        'Ketidakpastian',
        'Satuan',
        'Hasil',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'NilaiReferensi' => 'decimal:8',
            'NilaiTerukur' => 'decimal:8',
            'Koreksi' => 'decimal:8',
            'Ketidakpastian' => 'decimal:8',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function pelaksanaanKalibrasi(): BelongsTo
    {
        return $this->belongsTo(PelaksanaanKalibrasi::class, 'PelaksanaanKalibrasiId', 'Id');
    }

    public function titikUkurKalibrasi(): BelongsTo
    {
        return $this->belongsTo(TitikUkurKalibrasi::class, 'TitikUkurKalibrasiId', 'Id');
    }
}
