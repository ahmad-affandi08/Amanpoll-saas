<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Mencari akun tenant untuk masuk dan lupa kata sandi tanpa kode organisasi (PRD 8.1).
 *
 * Email hanya unik per organisasi, jadi satu email dapat memiliki beberapa akun.
 * Yang ikut hanya akun berstatus Aktif di organisasi berstatus Aktif. Pencarian
 * berjalan sebelum ada konteks organisasi; `Pengguna` memang tidak memakai scope
 * tenancy (ADR 0002). Kolom `Email` berkolasi `utf8mb4_unicode_ci`, sehingga
 * perbandingannya sudah tidak peka huruf besar.
 */
final class PencariAkunMasuk
{
    private const STATUS_AKTIF = 'Aktif';

    /** @return Collection<int, Pengguna> */
    public function akunAktif(string $email): Collection
    {
        return $this->kueriAkunAktif()->where('Email', $email)->get();
    }

    /**
     * Akun yang dipilih ulang dari daftar sesi; statusnya diperiksa lagi karena
     * akun atau organisasinya dapat dinonaktifkan sesudah kata sandinya dicocokkan.
     *
     * @param  list<string>  $daftarId
     * @return Collection<int, Pengguna>
     */
    public function akunAktifDenganId(array $daftarId): Collection
    {
        return $this->kueriAkunAktif()->whereIn('Id', $daftarId)->get();
    }

    /**
     * Akun yang kata sandinya cocok.
     *
     * Tanpa akun sama sekali tetap dihitung satu hash tiruan dengan biaya yang
     * sama dengan satu pemeriksaan, supaya lama jawaban tidak membocorkan apakah
     * email itu terdaftar. Hasilnya memang dibuang.
     *
     * @param  Collection<int, Pengguna>  $akun
     * @return Collection<int, Pengguna>
     */
    public function cocokkanKataSandi(Collection $akun, string $kataSandi): Collection
    {
        if ($akun->isEmpty()) {
            Hash::make($kataSandi);

            return $akun;
        }

        return $akun
            ->filter(fn (Pengguna $pengguna): bool => Hash::check($kataSandi, $pengguna->getAuthPassword()))
            ->values();
    }

    /** @return Builder<Pengguna> */
    private function kueriAkunAktif(): Builder
    {
        return Pengguna::query()
            ->with('organisasi:Id,Nama')
            ->where('Status', self::STATUS_AKTIF)
            ->whereHas('organisasi', fn (Builder $organisasi): Builder => $organisasi->where('Status', self::STATUS_AKTIF))
            ->orderBy('Id');
    }
}
