<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UsulanAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'UsulanAset';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'UnitOrganisasiId',
        'KategoriAsetId',
        'ModelAsetId',
        'NamaKebutuhan',
        'Jumlah',
        'EstimasiHargaSatuan',
        'Alasan',
        'JenisKebutuhan',
        'TahunKebutuhan',
        'Prioritas',
        'Status',
        'DiajukanOleh',
        'DiajukanPada',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'EstimasiHargaSatuan' => 'decimal:2',
            'TahunKebutuhan' => 'integer',
            'DiajukanPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(KategoriAset::class, 'KategoriAsetId', 'Id');
    }

    public function modelAset(): BelongsTo
    {
        return $this->belongsTo(ModelAset::class, 'ModelAsetId', 'Id');
    }

    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DiajukanOleh', 'Id');
    }
}
