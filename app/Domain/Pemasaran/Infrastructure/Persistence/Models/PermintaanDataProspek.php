<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\JenisPermintaanData;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Permintaan penghapusan atau anonimisasi data seorang prospek (MARKETING.md 27). */
final class PermintaanDataProspek extends ModelDasar
{
    protected $table = 'PermintaanDataProspek';

    public $timestamps = false;

    protected $fillable = ['ProspekId', 'EmailHash', 'Email', 'Jenis', 'Catatan', 'DimintaPada', 'DiprosesPada'];

    protected function casts(): array
    {
        return [
            'Jenis' => JenisPermintaanData::class,
            'DimintaPada' => 'immutable_datetime',
            'DiprosesPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }
}
