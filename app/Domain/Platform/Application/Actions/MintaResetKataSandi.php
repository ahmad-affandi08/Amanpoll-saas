<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Notifications\ResetKataSandiNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Selalu "berhasil" dari sudut pandang pemanggil (tidak membocorkan apakah
 * kombinasi organisasi+email terdaftar) -- konsisten dengan pesan generik
 * login. Email hanya unik per organisasi, jadi PenggunaId (bukan email)
 * yang dipakai sebagai kunci token.
 */
final class MintaResetKataSandi
{
    public function jalankan(string $kodeOrganisasi, string $email): void
    {
        $pengguna = Pengguna::query()
            ->join('Organisasi', 'Organisasi.Id', '=', 'Pengguna.OrganisasiId')
            ->where('Organisasi.Kode', $kodeOrganisasi)
            ->where('Organisasi.Status', 'Aktif')
            ->where('Pengguna.Email', $email)
            ->where('Pengguna.Status', 'Aktif')
            ->select('Pengguna.*')
            ->first();

        if (!$pengguna) {
            return;
        }

        $tokenMentah = Str::random(64);

        DB::table('TokenResetKataSandi')->updateOrInsert(
            ['PenggunaId' => $pengguna->Id],
            ['TokenHash' => hash('sha256', $tokenMentah), 'DibuatPada' => now()],
        );

        $pengguna->notify(new ResetKataSandiNotification($tokenMentah));
    }
}
