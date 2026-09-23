<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Core\Organisasi\KalenderOrganisasi;

/**
 * Kolom waktu berjam selalu disimpan sebagai UTC, dari mana pun nilainya datang.
 *
 * Eloquent menyimpan jam dinding objek waktu apa adanya: `asDateTime()`
 * mengembalikan objek Carbon tanpa mengubah zonanya, lalu `fromDateTime()`
 * memformat jamnya. Waktu `2026-09-01T01:00:00+07:00` karena itu tersimpan
 * sebagai pukul 01:00, padahal momennya pukul 18:00 UTC hari sebelumnya.
 * Aplikasi offline, webhook, dan API mengirim waktu berzona, jadi kesalahan ini
 * tidak bergantung pada satu jalur saja -- ia menunggu siapa pun yang lupa
 * mengonversi.
 *
 * Kolom tanggal saja (`date`, `immutable_date`) sengaja tidak disentuh: tanggal
 * kalender tidak punya zona, dan menggesernya ke UTC akan memindahkannya ke hari
 * sebelumnya.
 *
 * Tanggal tanpa jam (`2026-09-22`) untuk kolom berjam datang dari pemilih
 * tanggal di formulir dan berarti "hari itu" di rumah sakitnya, jadi dibaca
 * sebagai awal hari di zona organisasi -- bukan tengah malam UTC, yang di
 * Jakarta sudah pukul 07:00.
 */
trait MenyimpanWaktuDalamUtc
{
    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if ($value !== null && $value !== '' && $this->adalahKolomWaktuBerjam($key)) {
            $value = is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value)) === 1
                ? app(KalenderOrganisasi::class)->awalHari($value, $this->organisasiPemilikWaktu())
                : $this->asDateTime($value)->utc();
        }

        return parent::setAttribute($key, $value);
    }

    /** Organisasi yang kalendernya menentukan awal hari; null berarti konteks aktif atau zona bawaan. */
    private function organisasiPemilikWaktu(): ?string
    {
        $organisasiId = $this->getAttributes()['OrganisasiId'] ?? null;

        return is_string($organisasiId) && $organisasiId !== '' ? $organisasiId : null;
    }

    private function adalahKolomWaktuBerjam(string $key): bool
    {
        if (! $this->isDateAttribute($key)) {
            return false;
        }

        $cast = $this->getCasts()[$key] ?? null;

        // Tanpa cast berarti kolom stempel waktu model (DibuatPada, DiubahPada).
        if ($cast === null) {
            return true;
        }

        $jenis = strtolower(explode(':', (string) $cast, 2)[0]);

        return ! in_array($jenis, ['date', 'immutable_date'], true);
    }
}
