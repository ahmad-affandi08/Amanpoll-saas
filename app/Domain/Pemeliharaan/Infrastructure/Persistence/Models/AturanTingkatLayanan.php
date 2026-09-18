<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
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

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function tingkatLayanan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

}
