<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KontrakAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KontrakAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'KontrakId',
        'AsetId',
        'MulaiPada',
        'BerakhirPada',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'MulaiPada' => 'date',
            'BerakhirPada' => 'date',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function kontrak(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak::class, 'KontrakId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

}
