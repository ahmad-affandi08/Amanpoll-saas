<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Application\Services\PencariAkunMasuk;
use App\Domain\Platform\Notifications\ResetKataSandiNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mengirim tautan reset untuk setiap akun aktif yang memakai email itu (PRD 8.1).
 *
 * Email yang sama dapat terdaftar di beberapa organisasi; tiap akun menerima
 * surelnya sendiri yang menyebut nama organisasinya, dan tokennya terikat pada
 * akun itu. Selalu "berhasil" dari sudut pandang pemanggil, tanpa membocorkan
 * apakah akunnya terdaftar.
 */
final class MintaResetKataSandi
{
    public function __construct(private readonly PencariAkunMasuk $pencariAkun) {}

    public function jalankan(string $email): void
    {
        foreach ($this->pencariAkun->akunAktif($email) as $pengguna) {
            $tokenMentah = Str::random(64);

            DB::table('TokenResetKataSandi')->updateOrInsert(
                ['PenggunaId' => $pengguna->Id],
                ['TokenHash' => hash('sha256', $tokenMentah), 'DibuatPada' => now()],
            );

            $pengguna->notify(new ResetKataSandiNotification($tokenMentah));
        }
    }
}
