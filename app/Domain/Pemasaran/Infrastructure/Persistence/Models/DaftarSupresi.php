<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Shared\Infrastructure\Persistence\ModelDasar;

/** Kontak yang tidak boleh lagi menerima pesan pemasaran di kanalnya (MARKETING.md 27). */
final class DaftarSupresi extends ModelDasar
{
    protected $table = 'DaftarSupresi';

    public $timestamps = false;

    protected $fillable = ['Kanal', 'KontakHash', 'Kontak', 'Alasan', 'Catatan', 'DitambahkanPada'];

    protected function casts(): array
    {
        return [
            'Kanal' => KanalPesan::class,
            'Alasan' => AlasanSupresi::class,
            'DitambahkanPada' => 'immutable_datetime',
        ];
    }
}
