<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
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

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<Kontrak, $this>
     */
    public function kontrak(): BelongsTo
    {
        return $this->belongsTo(Kontrak::class, 'KontrakId', 'Id');
    }
}
