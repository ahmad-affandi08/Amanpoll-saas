<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PengirimanPanggilanBalikWeb extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PengirimanPanggilanBalikWeb';

    public $timestamps = false;

    public const STATUS_ANTRI = 'Antri';

    public const STATUS_BERHASIL = 'Berhasil';

    public const STATUS_GAGAL = 'Gagal';

    /** Status akhir setelah seluruh percobaan ulang habis. */
    public const STATUS_GAGAL_PERMANEN = 'GagalPermanen';

    public const BATAS_PERCOBAAN = 5;

    protected $fillable = [
        'OrganisasiId',
        'PanggilanBalikWebId',
        'Peristiwa',
        'MuatanData',
        'StatusHttp',
        'Respons',
        'Status',
        'Percobaan',
        'JadwalCobaLagiPada',
        'DikirimPada',
    ];

    protected function casts(): array
    {
        return [
            'MuatanData' => 'array',
            'StatusHttp' => 'integer',
            'Percobaan' => 'integer',
            'JadwalCobaLagiPada' => 'immutable_datetime',
            'DikirimPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<PanggilanBalikWeb, $this> */
    public function panggilanBalikWeb(): BelongsTo
    {
        return $this->belongsTo(PanggilanBalikWeb::class, 'PanggilanBalikWebId', 'Id');
    }
}
