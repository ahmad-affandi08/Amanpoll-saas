<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Kontak tambahan pada satu prospek (MARKETING.md 24). */
final class KontakProspek extends ModelDasar
{
    protected $table = 'KontakProspek';

    public $timestamps = false;

    protected $fillable = ['ProspekId', 'Nama', 'Email', 'Telepon', 'Jabatan', 'Utama'];

    protected function casts(): array
    {
        return ['Utama' => 'boolean', 'DibuatPada' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }
}
