<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KepatuhanAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KepatuhanAset';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const STATUS_BELUM_DIPERIKSA = 'BelumDiperiksa';

    public const STATUS_PATUH = 'Patuh';

    public const STATUS_TIDAK_PATUH = 'TidakPatuh';

    public const STATUS_KEDALUWARSA = 'Kedaluwarsa';

    /** @var list<string> */
    public const DAFTAR_STATUS = [
        self::STATUS_BELUM_DIPERIKSA,
        self::STATUS_PATUH,
        self::STATUS_TIDAK_PATUH,
        self::STATUS_KEDALUWARSA,
    ];

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'PersyaratanKepatuhanId',
        'Status',
        'TanggalPemeriksaan',
        'BerlakuSampai',
        'Catatan',
        'DiperiksaOleh',
    ];

    protected function casts(): array
    {
        return [
            'TanggalPemeriksaan' => 'date',
            'BerlakuSampai' => 'date',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
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
     * @return BelongsTo<PersyaratanKepatuhan, $this>
     */
    public function persyaratanKepatuhan(): BelongsTo
    {
        return $this->belongsTo(PersyaratanKepatuhan::class, 'PersyaratanKepatuhanId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function diperiksaOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DiperiksaOleh', 'Id');
    }
}
