<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Services;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use Illuminate\Support\Facades\DB;

/**
 * Kabar in-app kepada pengelola integrasi organisasi tentang pengantar notifikasinya (PRD 8.23).
 *
 * Dua kabar: penyedia milik organisasi mulai gagal, dan kuota WhatsApp bawaan habis.
 * Keduanya sekali saja per kejadian, bukan setiap kali pengiriman tertolak, supaya
 * kabar tentang notifikasi yang gagal tidak berubah menjadi banjir notifikasi.
 * Dipanggil juga dari worker antrian, jadi konteks organisasi dipasang sementara.
 */
final class PemberitahuLayananPengirim
{
    public const PERISTIWA_PENYEDIA_BERMASALAH = 'Layanan.PenyediaBermasalah';

    public const PERISTIWA_KUOTA_WHATSAPP_HABIS = 'Layanan.KuotaWhatsAppHabis';

    private const IZIN_PENERIMA = 'Integrasi.Kelola';

    public function __construct(
        private readonly LayananNotifikasi $layananNotifikasi,
        private readonly KonteksOrganisasi $konteks,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    /** @param  'Email'|'WhatsApp'  $kategori */
    public function penyediaBermasalah(string $organisasiId, string $kategori, string $galat): void
    {
        $akibat = $kategori === 'Email'
            ? 'Sementara ini email notifikasi dikirim lewat email Amanpoll.'
            : 'Notifikasi WhatsApp ke staf tidak terkirim sampai penyedianya diperbaiki.';

        $this->kirimKePengelola(
            $organisasiId,
            self::PERISTIWA_PENYEDIA_BERMASALAH,
            "{$kategori} milik organisasi gagal mengirim",
            "{$akibat} Galat terakhir: {$galat}",
        );
    }

    public function kuotaWhatsAppHabis(string $organisasiId, ?int $batas): void
    {
        if ($this->sudahDiberitahuBulanIni($organisasiId)) {
            return;
        }

        $jumlah = $batas === null ? '' : " ({$batas} pesan)";

        $this->kirimKePengelola(
            $organisasiId,
            self::PERISTIWA_KUOTA_WHATSAPP_HABIS,
            'Kuota WhatsApp bawaan bulan ini habis',
            "Notifikasi WhatsApp lewat nomor Amanpoll sudah mencapai batas paket{$jumlah}. Notifikasi in-app tetap berjalan. "
            .'Naikkan paket atau pasang nomor WhatsApp milik organisasi di menu Email & WhatsApp.',
        );
    }

    private function kirimKePengelola(string $organisasiId, string $peristiwa, string $judul, string $isi): void
    {
        $sebelumnya = $this->konteks->id();
        $this->konteks->tetapkan($organisasiId);

        try {
            foreach ($this->pengelola($organisasiId) as $penggunaId) {
                $this->layananNotifikasi->kirim(
                    $penggunaId,
                    $peristiwa,
                    $isi,
                    $judul,
                    kanal: [KanalNotifikasi::InApp->value],
                );
            }
        } finally {
            $this->konteks->tetapkan($sebelumnya);
        }
    }

    private function sudahDiberitahuBulanIni(string $organisasiId): bool
    {
        return Notifikasi::query()
            ->withoutGlobalScope(ScopeOrganisasi::class)
            ->where('OrganisasiId', $organisasiId)
            ->where('JenisPeristiwa', self::PERISTIWA_KUOTA_WHATSAPP_HABIS)
            ->where('JadwalKirimPada', '>=', $this->kalender->sekarang($organisasiId)->startOfMonth()->utc())
            ->exists();
    }

    /** @return list<string> */
    private function pengelola(string $organisasiId): array
    {
        $penggunaId = DB::table('PenggunaPeran as pp')
            ->join('PeranIzin as pi', 'pi.PeranId', '=', 'pp.PeranId')
            ->join('Izin as i', 'i.Id', '=', 'pi.IzinId')
            ->join('Pengguna as p', 'p.Id', '=', 'pp.PenggunaId')
            ->where('pp.OrganisasiId', $organisasiId)
            ->where('i.Kode', self::IZIN_PENERIMA)
            ->where('p.Status', 'Aktif')
            ->whereNull('p.DihapusPada')
            ->where(fn ($q) => $q->whereNull('pp.BerlakuMulai')->orWhere('pp.BerlakuMulai', '<=', now()))
            ->where(fn ($q) => $q->whereNull('pp.BerlakuSampai')->orWhere('pp.BerlakuSampai', '>=', now()))
            ->distinct()
            ->pluck('pp.PenggunaId');

        return array_values(array_map(strval(...), $penggunaId->all()));
    }
}
