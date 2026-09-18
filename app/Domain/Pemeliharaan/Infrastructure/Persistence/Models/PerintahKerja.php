<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PerintahKerja extends ModelDasar
{
    use SoftDeletes, MilikOrganisasi;

    protected $table = 'PerintahKerja';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';
    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'KeluhanId',
        'TingkatLayananId',
        'Jenis',
        'Judul',
        'Deskripsi',
        'Prioritas',
        'Status',
        'LokasiId',
        'UnitOrganisasiId',
        'DijadwalkanMulaiPada',
        'DijadwalkanSelesaiPada',
        'DiterimaPada',
        'DimulaiPada',
        'DiselesaikanPada',
        'DitutupPada',
        'BatasResponsPada',
        'BatasPenyelesaianPada',
        'PersentaseSelesai',
        'MembutuhkanWaktuHenti',
        'MembutuhkanPersetujuan',
        'RingkasanPenyelesaian',
        'DibuatOleh',
        'Versi',
    ];

    protected function casts(): array
    {
        return [
            'DijadwalkanMulaiPada' => 'immutable_datetime',
            'DijadwalkanSelesaiPada' => 'immutable_datetime',
            'DiterimaPada' => 'immutable_datetime',
            'DimulaiPada' => 'immutable_datetime',
            'DiselesaikanPada' => 'immutable_datetime',
            'DitutupPada' => 'immutable_datetime',
            'BatasResponsPada' => 'immutable_datetime',
            'BatasPenyelesaianPada' => 'immutable_datetime',
            'PersentaseSelesai' => 'decimal:2',
            'MembutuhkanWaktuHenti' => 'boolean',
            'MembutuhkanPersetujuan' => 'boolean',
            'Versi' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function keluhan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan::class, 'KeluhanId', 'Id');
    }

    public function tingkatLayanan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiId', 'Id');
    }

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DibuatOleh', 'Id');
    }

}
