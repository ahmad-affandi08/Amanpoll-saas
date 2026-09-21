<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SertifikasiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'SertifikasiAset';

    public $timestamps = false;

    public const STATUS_AKTIF = 'Aktif';

    public const STATUS_KEDALUWARSA = 'Kedaluwarsa';

    public const STATUS_DICABUT = 'Dicabut';

    /** @var list<string> */
    public const DAFTAR_STATUS = [self::STATUS_AKTIF, self::STATUS_KEDALUWARSA, self::STATUS_DICABUT];

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'JenisSertifikasi',
        'NomorSertifikat',
        'Penerbit',
        'TerbitPada',
        'BerlakuSampai',
        'Status',
        'BerkasId',
    ];

    protected function casts(): array
    {
        return [
            'TerbitPada' => 'date',
            'BerlakuSampai' => 'date',
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
     * @return BelongsTo<Aset, $this>
     */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    /**
     * @return BelongsTo<Berkas, $this>
     */
    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class, 'BerkasId', 'Id');
    }
}
