<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WaktuHentiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'WaktuHentiAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'PerintahKerjaId',
        'MulaiPada',
        'SelesaiPada',
        'DurasiMenit',
        'Jenis',
        'Alasan',
    ];

    protected function casts(): array
    {
        return [
            'MulaiPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
            'DurasiMenit' => 'integer',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

}
