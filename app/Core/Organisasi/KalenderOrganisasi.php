<?php

declare(strict_types=1);

namespace App\Core\Organisasi;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Kalender lokal sebuah organisasi.
 *
 * Waktu disimpan UTC, tetapi "hari ini", "tanggal jatuh tempo", dan "awal
 * bulan" adalah hal kalender, dan kalender itu milik rumah sakitnya. Rumah
 * sakit di Jayapura sudah berganti hari dua jam lebih dulu daripada Jakarta,
 * dan sembilan jam lebih dulu daripada UTC. Memakai tanggal UTC untuk
 * keputusan kalender membuat alat yang jatuh tempo hari ini baru tampil
 * jatuh tempo pukul 07:00 WIB, dan tren harian menaruh kejadian dini hari ke
 * hari sebelumnya.
 *
 * Tanggal dikembalikan dalam bentuk yang sama dengan cara kolom `date` dibaca
 * Eloquent: tengah malam UTC dari tanggal kalendernya. Dengan begitu
 * `hariIni()` dapat langsung dibandingkan dengan kolom tanggal, dan aritmetika
 * hari di sekitarnya tidak perlu diubah.
 *
 * Tanpa konteks organisasi (pekerjaan tingkat platform, konsol), dipakai zona
 * bawaan `amanpoll.zona_waktu_default`.
 */
final class KalenderOrganisasi
{
    /** @var array<string, string> */
    private array $zonaTerbaca = [];

    public function __construct(private readonly KonteksOrganisasi $konteks) {}

    /** Zona waktu IANA milik organisasi, mis. `Asia/Jayapura`. */
    public function zona(?string $organisasiId = null): string
    {
        $organisasiId ??= $this->konteks->id();

        if ($organisasiId === null) {
            return self::zonaBawaan();
        }

        return $this->zonaTerbaca[$organisasiId] ??= trim((string) Organisasi::query()
            ->whereKey($organisasiId)
            ->value('ZonaWaktu')) ?: self::zonaBawaan();
    }

    /** Saat ini, dalam zona organisasi; untuk dicetak kepada manusia. */
    public function sekarang(?string $organisasiId = null): CarbonImmutable
    {
        return CarbonImmutable::now($this->zona($organisasiId));
    }

    /**
     * Tanggal kalender hari ini di organisasi, sebagai tengah malam UTC.
     *
     * Bentuknya sengaja sama dengan nilai kolom `date` yang dibaca Eloquent,
     * supaya dapat dibandingkan dan dihitung selisih harinya secara langsung.
     */
    public function hariIni(?string $organisasiId = null): CarbonImmutable
    {
        return $this->hariPada(CarbonImmutable::now(), $organisasiId);
    }

    /**
     * Tanggal kalender tempat sebuah momen jatuh di organisasi, sebagai tengah
     * malam UTC; bentuknya sama dengan `hariIni()`.
     */
    public function hariPada(DateTimeInterface $momen, ?string $organisasiId = null): CarbonImmutable
    {
        return CarbonImmutable::parse($this->tanggalLokal($momen, $organisasiId), 'UTC');
    }

    /** Tanggal kalender (Y-m-d) tempat sebuah momen jatuh di organisasi. */
    public function tanggalLokal(DateTimeInterface $momen, ?string $organisasiId = null): string
    {
        return CarbonImmutable::instance($momen)->setTimezone($this->zona($organisasiId))->toDateString();
    }

    /**
     * Momen UTC saat tanggal kalender itu dimulai di organisasi.
     *
     * Untuk menyaring kolom waktu berjam dengan tanggal pilihan pengguna.
     * Objek waktu diperlakukan sebagai tanggal kalendernya saja.
     */
    public function awalHari(DateTimeInterface|string $tanggal, ?string $organisasiId = null): CarbonImmutable
    {
        $kalender = $tanggal instanceof DateTimeInterface ? $tanggal->format('Y-m-d') : substr(trim($tanggal), 0, 10);

        return CarbonImmutable::parse($kalender, $this->zona($organisasiId))->utc();
    }

    /**
     * Momen UTC saat tanggal kalender SESUDAHNYA dimulai.
     *
     * Batas atas eksklusif: `>= awalHari(x) AND < awalHariBerikutnya(x)`
     * mencakup seluruh hari x tanpa bergantung pada presisi detik.
     */
    public function awalHariBerikutnya(DateTimeInterface|string $tanggal, ?string $organisasiId = null): CarbonImmutable
    {
        return $this->awalHari($tanggal, $organisasiId)
            ->setTimezone($this->zona($organisasiId))
            ->addDay()
            ->utc();
    }

    /**
     * Selisih zona organisasi terhadap UTC untuk SQL, mis. `+07:00`.
     *
     * Dipakai `CONVERT_TZ(kolom, '+00:00', offset)`: tabel zona bernama MySQL
     * tidak tersedia di shared hosting. Zona Indonesia tidak mengenal waktu
     * musim panas, jadi selisih saat ini berlaku untuk seluruh rentang.
     */
    public function offsetSql(?string $organisasiId = null): string
    {
        return $this->sekarang($organisasiId)->format('P');
    }

    public static function zonaBawaan(): string
    {
        return (string) config('amanpoll.zona_waktu_default', 'Asia/Jakarta');
    }
}
