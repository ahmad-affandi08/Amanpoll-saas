<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LokasiGudang extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis;

    protected $table = 'LokasiGudang';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'GudangId',
        'IndukId',
        'Kode',
        'Nama',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'LGD';
    }

    /**
     * Kode hanya perlu unik dalam satu Gudang, sesuai indeks uniknya.
     *
     * @return array<string, mixed>
     */
    public function lingkupKode(): array
    {
        return ['GudangId' => $this->GudangId];
    }

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<Gudang, $this>
     */
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'GudangId', 'Id');
    }

    /**
     * @return BelongsTo<LokasiGudang, $this>
     */
    public function induk(): BelongsTo
    {
        return $this->belongsTo(LokasiGudang::class, 'IndukId', 'Id');
    }
}
