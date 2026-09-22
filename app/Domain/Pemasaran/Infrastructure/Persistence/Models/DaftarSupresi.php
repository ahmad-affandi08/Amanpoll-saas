<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Shared\Infrastructure\Persistence\ModelDasar;

/** Alamat yang tidak boleh lagi menerima pesan pemasaran (MARKETING.md 27). */
final class DaftarSupresi extends ModelDasar
{
    protected $table = 'DaftarSupresi';

    public $timestamps = false;

    protected $fillable = ['EmailHash', 'Email', 'Alasan', 'Catatan', 'DitambahkanPada'];

    protected function casts(): array
    {
        return [
            'Alasan' => AlasanSupresi::class,
            'DitambahkanPada' => 'immutable_datetime',
        ];
    }
}
