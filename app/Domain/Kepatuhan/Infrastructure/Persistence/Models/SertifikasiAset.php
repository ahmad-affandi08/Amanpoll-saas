<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SertifikasiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'SertifikasiAset';

    public $timestamps = false;

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

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas::class, 'BerkasId', 'Id');
    }

}
