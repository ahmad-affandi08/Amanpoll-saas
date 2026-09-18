<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ButirTemplatDaftarPeriksa extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'ButirTemplatDaftarPeriksa';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'TemplatDaftarPeriksaId',
        'Urutan',
        'Kode',
        'Pertanyaan',
        'TipeJawaban',
        'Satuan',
        'Wajib',
        'NilaiMinimum',
        'NilaiMaksimum',
        'Pilihan',
        'BuktiFotoWajib',
        'MemicuTemuanJika',
    ];

    protected function casts(): array
    {
        return [
            'Urutan' => 'integer',
            'Wajib' => 'boolean',
            'NilaiMinimum' => 'decimal:6',
            'NilaiMaksimum' => 'decimal:6',
            'Pilihan' => 'array',
            'BuktiFotoWajib' => 'boolean',
            'MemicuTemuanJika' => 'array',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function templatDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa::class, 'TemplatDaftarPeriksaId', 'Id');
    }

}
