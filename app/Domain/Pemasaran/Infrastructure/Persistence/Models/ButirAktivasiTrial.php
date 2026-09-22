<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\ButirAktivasi;
use App\Shared\Infrastructure\Persistence\HanyaTambah;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu butir checklist yang sudah selesai (MARKETING.md 12). */
final class ButirAktivasiTrial extends ModelDasar
{
    use HanyaTambah;

    protected $table = 'ButirAktivasiTrial';

    public $timestamps = false;

    protected $fillable = ['TrialId', 'Butir', 'SelesaiPada'];

    protected function casts(): array
    {
        return [
            'Butir' => ButirAktivasi::class,
            'SelesaiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Trial, $this> */
    public function trial(): BelongsTo
    {
        return $this->belongsTo(Trial::class, 'TrialId', 'Id');
    }
}
