<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu langkah dalam sequence: template apa, hari ke berapa (MARKETING.md 15). */
final class LangkahSequenceEmail extends ModelDasar
{
    protected $table = 'LangkahSequenceEmail';

    public $timestamps = false;

    protected $fillable = [
        'SequenceEmailPemasaranId',
        'TemplateEmailPemasaranId',
        'Urutan',
        'HariKe',
        'Aktif',
    ];

    protected function casts(): array
    {
        return ['Urutan' => 'integer', 'HariKe' => 'integer', 'Aktif' => 'boolean'];
    }

    /** @return BelongsTo<SequenceEmailPemasaran, $this> */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(SequenceEmailPemasaran::class, 'SequenceEmailPemasaranId', 'Id');
    }

    /** @return BelongsTo<TemplateEmailPemasaran, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateEmailPemasaran::class, 'TemplateEmailPemasaranId', 'Id');
    }
}
