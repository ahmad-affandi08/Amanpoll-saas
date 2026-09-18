<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Aset extends ModelDasar
{
    use SoftDeletes, MilikOrganisasi;

    protected $table = 'Aset';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';
    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'UnitOrganisasiId',
        'LokasiId',
        'KategoriAsetId',
        'ModelAsetId',
        'PenyediaId',
        'KodeAset',
        'Nama',
        'NomorSeri',
        'NomorInventaris',
        'NomorRegistrasiEksternal',
        'TanggalPerolehan',
        'TanggalMulaiOperasi',
        'TanggalAkhirOperasi',
        'HargaPerolehan',
        'NilaiResidu',
        'MataUang',
        'SumberDana',
        'MetodePenyusutan',
        'UmurManfaatBulan',
        'Status',
        'Kondisi',
        'TingkatKritis',
        'KodeQr',
        'NfcUid',
        'KodeBatang',
        'Catatan',
        'Versi',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'TanggalPerolehan' => 'date',
            'TanggalMulaiOperasi' => 'date',
            'TanggalAkhirOperasi' => 'date',
            'HargaPerolehan' => 'decimal:2',
            'NilaiResidu' => 'decimal:2',
            'UmurManfaatBulan' => 'integer',
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

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiId', 'Id');
    }

    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset::class, 'KategoriAsetId', 'Id');
    }

    public function modelAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset::class, 'ModelAsetId', 'Id');
    }

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia::class, 'PenyediaId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DibuatOleh', 'Id');
    }

}
