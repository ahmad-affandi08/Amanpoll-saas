<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu email pemasaran yang dijadwalkan atau sudah dikirim (MARKETING.md 15). */
final class PengirimanEmailPemasaran extends ModelDasar
{
    protected $table = 'PengirimanEmailPemasaran';

    public $timestamps = false;

    protected $fillable = [
        'ProspekId',
        'Email',
        'TemplateEmailPemasaranId',
        'PendaftaranSequenceId',
        'LangkahSequenceEmailId',
        'KunciIdempotensi',
        'Status',
        'Subjek',
        'IdPesanPenyedia',
        'Percobaan',
        'Galat',
        'JadwalPada',
        'DikirimPada',
        'DiperbaruiStatusPada',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusPengirimanEmail::class,
            'Percobaan' => 'integer',
            'JadwalPada' => 'immutable_datetime',
            'DikirimPada' => 'immutable_datetime',
            'DiperbaruiStatusPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }

    /** @return BelongsTo<TemplateEmailPemasaran, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateEmailPemasaran::class, 'TemplateEmailPemasaranId', 'Id');
    }

    /** @return BelongsTo<PendaftaranSequence, $this> */
    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(PendaftaranSequence::class, 'PendaftaranSequenceId', 'Id');
    }
}
