<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailMutasiStok extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailMutasiStok';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'MutasiStokId',
        'SukuCadangId',
        'KelompokSukuCadangId',
        'Jumlah',
        'HargaSatuan',
        'LokasiGudangAsalId',
        'LokasiGudangTujuanId',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'HargaSatuan' => 'decimal:2',
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
     * @return BelongsTo<MutasiStok, $this>
     */
    public function mutasiStok(): BelongsTo
    {
        return $this->belongsTo(MutasiStok::class, 'MutasiStokId', 'Id');
    }

    /**
     * @return BelongsTo<SukuCadang, $this>
     */
    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(SukuCadang::class, 'SukuCadangId', 'Id');
    }

    /**
     * @return BelongsTo<KelompokSukuCadang, $this>
     */
    public function kelompokSukuCadang(): BelongsTo
    {
        return $this->belongsTo(KelompokSukuCadang::class, 'KelompokSukuCadangId', 'Id');
    }

    /**
     * @return BelongsTo<LokasiGudang, $this>
     */
    public function lokasiGudangAsal(): BelongsTo
    {
        return $this->belongsTo(LokasiGudang::class, 'LokasiGudangAsalId', 'Id');
    }

    /**
     * @return BelongsTo<LokasiGudang, $this>
     */
    public function lokasiGudangTujuan(): BelongsTo
    {
        return $this->belongsTo(LokasiGudang::class, 'LokasiGudangTujuanId', 'Id');
    }
}
