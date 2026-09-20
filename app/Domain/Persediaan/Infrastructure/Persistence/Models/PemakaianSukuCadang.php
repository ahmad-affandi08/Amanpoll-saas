<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PemakaianSukuCadang extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PemakaianSukuCadang';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PerintahKerjaId',
        'SukuCadangId',
        'GudangId',
        'KelompokSukuCadangId',
        'Jumlah',
        'HargaSatuan',
        'MutasiStokId',
        'DipakaiOleh',
        'DipakaiPada',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'HargaSatuan' => 'decimal:2',
            'DipakaiPada' => 'immutable_datetime',
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
     * @return BelongsTo<SukuCadang, $this>
     */
    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(SukuCadang::class, 'SukuCadangId', 'Id');
    }

    /**
     * @return BelongsTo<Gudang, $this>
     */
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'GudangId', 'Id');
    }

    /**
     * @return BelongsTo<KelompokSukuCadang, $this>
     */
    public function kelompokSukuCadang(): BelongsTo
    {
        return $this->belongsTo(KelompokSukuCadang::class, 'KelompokSukuCadangId', 'Id');
    }

    /**
     * @return BelongsTo<MutasiStok, $this>
     */
    public function mutasiStok(): BelongsTo
    {
        return $this->belongsTo(MutasiStok::class, 'MutasiStokId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function dipakaiOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DipakaiOleh', 'Id');
    }
}
