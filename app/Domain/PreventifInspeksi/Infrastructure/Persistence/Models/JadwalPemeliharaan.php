<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JadwalPemeliharaan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'JadwalPemeliharaan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'RencanaPemeliharaanAsetId',
        'PerintahKerjaId',
        'TanggalJadwal',
        'Status',
        'DihasilkanOtomatis',
    ];

    protected function casts(): array
    {
        return [
            'TanggalJadwal' => 'date',
            'DihasilkanOtomatis' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function rencanaPemeliharaanAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset::class, 'RencanaPemeliharaanAsetId', 'Id');
    }

    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

}
