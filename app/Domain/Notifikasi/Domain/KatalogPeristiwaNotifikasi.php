<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain;

use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;

/** Katalog kode JenisPeristiwa yang dikenal platform. */
final class KatalogPeristiwaNotifikasi
{
    /**
     * Peristiwa yang menuntut tindakan segera dari penerimanya, jadi WhatsApp menyala tanpa
     * diminta. Peristiwa lain ramai atau sekadar kabar (stok, hasil persetujuan) sehingga
     * WhatsApp-nya baru menyala bila pengguna sendiri menyalakannya di preferensi.
     *
     * @var list<string>
     */
    private const WHATSAPP_BAWAAN_AKTIF = [
        'PerintahKerja.Ditugaskan',
        'Keluhan.Baru',
        'Keluhan.Sla.Mendekati',
        'Keluhan.Sla.Terlewati',
    ];

    /**
     * @return array<string, string>
     */
    public static function daftar(): array
    {
        return [
            'PerintahKerja.Ditugaskan' => 'Perintah kerja ditugaskan kepada Anda',
            'Persetujuan.PerluTindakan' => 'Ada permintaan persetujuan yang perlu tindakan Anda',
            'Persetujuan.Disetujui' => 'Permintaan persetujuan Anda disetujui',
            'Persetujuan.Ditolak' => 'Permintaan persetujuan Anda ditolak',
            'Stok.MinimumTercapai' => 'Stok suku cadang mencapai atau di bawah batas minimum',
            'Keluhan.Baru' => 'Keluhan baru masuk sesuai aturan routing kategori',
            'Keluhan.Sla.Mendekati' => 'SLA keluhan mendekati batas',
            'Keluhan.Sla.Terlewati' => 'SLA keluhan telah terlewati',
            'Layanan.PenyediaBermasalah' => 'Email atau WhatsApp milik organisasi gagal mengirim notifikasi',
            'Layanan.KuotaWhatsAppHabis' => 'Kuota WhatsApp bawaan bulan ini habis',
        ];
    }

    /**
     * @return list<string>
     */
    public static function kodeDikenal(): array
    {
        return array_keys(self::daftar());
    }

    public static function label(string $kode): ?string
    {
        return self::daftar()[$kode] ?? null;
    }

    /** Nilai preferensi selama pengguna belum pernah mengubahnya. In-app dan email menyala untuk semua peristiwa. */
    public static function aktifBawaan(string $kode, KanalNotifikasi $kanal): bool
    {
        return $kanal !== KanalNotifikasi::WhatsApp || in_array($kode, self::WHATSAPP_BAWAAN_AKTIF, true);
    }
}
