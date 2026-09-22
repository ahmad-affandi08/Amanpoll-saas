<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Entri timeline yang ditulis manusia (MARKETING.md 7). */
final class AktivitasProspek extends ModelDasar
{
    protected $table = 'AktivitasProspek';

    public $timestamps = false;

    protected $fillable = ['ProspekId', 'AktorPlatformId', 'Jenis', 'Judul', 'Isi', 'TerjadiPada'];

    protected function casts(): array
    {
        return ['TerjadiPada' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }
}
