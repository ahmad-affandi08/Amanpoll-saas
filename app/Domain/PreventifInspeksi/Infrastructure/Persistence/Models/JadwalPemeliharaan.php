<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
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

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<RencanaPemeliharaanAset, $this> */
    public function rencanaPemeliharaanAset(): BelongsTo
    {
        return $this->belongsTo(RencanaPemeliharaanAset::class, 'RencanaPemeliharaanAsetId', 'Id');
    }

    /** @return BelongsTo<PerintahKerja, $this> */
    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }
}
