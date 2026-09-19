<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris saldo stok per (Gudang, LokasiGudang, SukuCadang, KelompokSukuCadang).
 * TIDAK PERNAH diubah langsung dari Controller/Request -- satu-satunya jalur
 * penulisan adalah lewat PostingMutasiStok dan aksi Reservasi (Gate 10).
 */
final class StokSukuCadang extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'StokSukuCadang';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'GudangId',
        'LokasiGudangId',
        'SukuCadangId',
        'KelompokSukuCadangId',
        'JumlahTersedia',
        'JumlahDipesan',
        'JumlahDitahan',
        'Versi',
    ];

    protected function casts(): array
    {
        return [
            'JumlahTersedia' => 'decimal:4',
            'JumlahDipesan' => 'decimal:4',
            'JumlahDitahan' => 'decimal:4',
            'Versi' => 'integer',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /**
     * Kuantitas bersih yang boleh dijanjikan ke pemakai baru: stok fisik
     * dikurangi yang sudah ditahan reservasi (BUKAN dikurangi lagi oleh
     * JumlahDipesan, karena itu barang masuk, bukan keluar).
     */
    public function jumlahTersediaBersih(): float
    {
        return (float) $this->JumlahTersedia - (float) $this->JumlahDitahan;
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
    public function lokasiGudang(): BelongsTo
    {
        return $this->belongsTo(LokasiGudang::class, 'LokasiGudangId', 'Id');
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
}
