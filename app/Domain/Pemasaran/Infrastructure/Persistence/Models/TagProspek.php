<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Label bebas pada prospek (MARKETING.md 24). */
final class TagProspek extends ModelDasar
{
    protected $table = 'TagProspek';

    public $timestamps = false;

    protected $fillable = ['Nama'];

    protected function casts(): array
    {
        return ['DibuatPada' => 'immutable_datetime'];
    }

    /** @return BelongsToMany<Prospek, $this> */
    public function prospek(): BelongsToMany
    {
        return $this->belongsToMany(Prospek::class, 'ProspekTag', 'TagProspekId', 'ProspekId');
    }
}
