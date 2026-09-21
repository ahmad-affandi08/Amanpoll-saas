<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SinkronisasiEksternal extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'SinkronisasiEksternal';

    public $timestamps = false;

    public const STATUS_DIPROSES = 'Diproses';

    public const STATUS_BERHASIL = 'Berhasil';

    public const STATUS_SEBAGIAN = 'Sebagian';

    public const STATUS_GAGAL = 'Gagal';

    public const ARAH_TARIK = 'Tarik';

    public const ARAH_DORONG = 'Dorong';

    protected $fillable = [
        'OrganisasiId',
        'IntegrasiEksternalId',
        'JenisProses',
        'Arah',
        'Status',
        'JumlahData',
        'JumlahBerhasil',
        'JumlahGagal',
        'PesanKesalahan',
        'MulaiPada',
        'SelesaiPada',
    ];

    protected function casts(): array
    {
        return [
            'JumlahData' => 'integer',
            'JumlahBerhasil' => 'integer',
            'JumlahGagal' => 'integer',
            'MulaiPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function integrasiEksternal(): BelongsTo
    {
        return $this->belongsTo(IntegrasiEksternal::class, 'IntegrasiEksternalId', 'Id');
    }
}
