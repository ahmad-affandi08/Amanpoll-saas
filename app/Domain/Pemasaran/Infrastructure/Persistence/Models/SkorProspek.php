<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sumbangan satu peristiwa terhadap skor prospek (MARKETING.md 5.4). */
final class SkorProspek extends ModelDasar
{
    protected $table = 'SkorProspek';

    public $timestamps = false;

    protected $fillable = ['ProspekId', 'AturanSkorProspekId', 'Peristiwa', 'Bobot', 'DihitungPada'];

    protected function casts(): array
    {
        return ['Bobot' => 'integer', 'DihitungPada' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }

    /**
     * Aturan yang menghasilkan sumbangan ini. Boleh kosong: aturan yang dihapus
     * tidak ikut menghapus penjelasan skor yang terlanjur dihitung darinya.
     *
     * @return BelongsTo<AturanSkorProspek, $this>
     */
    public function aturan(): BelongsTo
    {
        return $this->belongsTo(AturanSkorProspek::class, 'AturanSkorProspekId', 'Id');
    }
}
