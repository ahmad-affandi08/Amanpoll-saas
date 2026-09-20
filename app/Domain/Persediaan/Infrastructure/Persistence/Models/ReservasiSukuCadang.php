<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ReservasiSukuCadang extends ModelDasar
{
    use MilikOrganisasi;

    public const STATUS_AKTIF = 'Aktif';

    public const STATUS_DILEPAS = 'Dilepas';

    public const STATUS_DIPAKAI = 'Dipakai';

    public const STATUS_KADALUARSA = 'Kadaluarsa';

    protected $table = 'ReservasiSukuCadang';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PerintahKerjaId',
        'GudangId',
        'SukuCadangId',
        'Jumlah',
        'Status',
        'KadaluarsaPada',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'KadaluarsaPada' => 'immutable_datetime',
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
     * @return BelongsTo<PerintahKerja, $this>
     */
    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    /**
     * @return BelongsTo<Gudang, $this>
     */
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'GudangId', 'Id');
    }

    /**
     * @return BelongsTo<SukuCadang, $this>
     */
    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(SukuCadang::class, 'SukuCadangId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
    }
}
