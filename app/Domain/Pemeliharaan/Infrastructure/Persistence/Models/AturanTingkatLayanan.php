<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AturanTingkatLayanan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'AturanTingkatLayanan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'TingkatLayananId',
        'Prioritas',
        'MenitRespons',
        'MenitMulaiPengerjaan',
        'MenitPenyelesaian',
        'MenghitungJamKerja',
    ];

    protected function casts(): array
    {
        return [
            'MenitRespons' => 'integer',
            'MenitMulaiPengerjaan' => 'integer',
            'MenitPenyelesaian' => 'integer',
            'MenghitungJamKerja' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<TingkatLayanan, $this> */
    public function tingkatLayanan(): BelongsTo
    {
        return $this->belongsTo(TingkatLayanan::class, 'TingkatLayananId', 'Id');
    }
}
