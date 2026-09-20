<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RelasiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RelasiAset';

    public const JENIS_KOMPONEN = 'Komponen';

    public const JENIS_TERKAIT = 'Terkait';

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
    public function asetInduk(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetIndukId', 'Id');
    }

    /**
     * @return BelongsTo<Aset, $this>
     */
    public function asetAnak(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetAnakId', 'Id');
    }
}
