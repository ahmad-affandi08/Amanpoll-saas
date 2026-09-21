<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TransaksiAnggaran extends ModelDasar
{
    use MilikOrganisasi;

    public const JENIS_KOMITMEN = 'Komitmen';

    public const JENIS_REALISASI = 'Realisasi';

    public const JENIS_PELEPASAN_KOMITMEN = 'PelepasanKomitmen';

    public const JENIS_PENYESUAIAN = 'Penyesuaian';

    public const DAFTAR_JENIS = [
        self::JENIS_KOMITMEN,
        self::JENIS_REALISASI,
        self::JENIS_PELEPASAN_KOMITMEN,
        self::JENIS_PENYESUAIAN,
    ];

    protected $table = 'TransaksiAnggaran';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PosAnggaranId',
        'Jenis',
        'ReferensiJenis',
        'ReferensiId',
        'Jumlah',
        'Tanggal',
        'Keterangan',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:2',
            'Tanggal' => 'date',
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
     * @return BelongsTo<PosAnggaran, $this>
     */
    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'PosAnggaranId', 'Id');
    }
}
