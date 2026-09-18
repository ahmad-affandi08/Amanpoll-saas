<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RelasiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RelasiAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'AsetIndukId',
        'AsetAnakId',
        'JenisRelasi',
        'Jumlah',
        'MulaiPada',
        'SelesaiPada',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'MulaiPada' => 'date',
            'SelesaiPada' => 'date',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function asetInduk(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetIndukId', 'Id');
    }

    public function asetAnak(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetAnakId', 'Id');
    }

}
