<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LayananKontrak extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'LayananKontrak';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'KontrakId',
        'Nama',
        'Deskripsi',
        'Kuota',
        'Satuan',
        'Terpakai',
    ];

    protected function casts(): array
    {
        return [
            'Kuota' => 'decimal:4',
            'Terpakai' => 'decimal:4',
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

}
