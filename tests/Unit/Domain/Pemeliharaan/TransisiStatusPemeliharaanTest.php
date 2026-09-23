<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Pemeliharaan;

use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use PHPUnit\Framework\TestCase;

/**
 * Mesin status keluhan dan perintah kerja (FASE 26.01 "State transition").
 *
 * Matriks ditulis ulang di sini dengan sengaja: ia adalah spesifikasi alur
 * triase dan pengerjaan. Setiap pasangan asal-tujuan diperiksa lewat
 * `dapatBeralihKe()`, sehingga menambah satu jalan pintas (mis. Baru langsung
 * Selesai) atau memutus satu jalan sah sama-sama menggagalkan test ini.
 */
final class TransisiStatusPemeliharaanTest extends TestCase
{
    public function test_setiap_pasangan_status_keluhan_mengikuti_matriks_triase(): void
    {
        $matriks = [
            'Baru' => ['Ditinjau', 'Ditolak', 'Dibatalkan'],
            'Ditinjau' => ['Diterima', 'Ditolak', 'Dibatalkan'],
            'Diterima' => ['Diproses'],
            'Diproses' => ['Selesai'],
            'Selesai' => ['Ditutup', 'Diproses'],
            'Ditutup' => [],
            'Ditolak' => [],
            'Dibatalkan' => [],
        ];

        $this->assertSame(array_keys($matriks), array_map(fn (StatusKeluhan $s): string => $s->value, StatusKeluhan::cases()));

        foreach (StatusKeluhan::cases() as $asal) {
            foreach (StatusKeluhan::cases() as $tujuan) {
                $this->assertSame(
                    in_array($tujuan->value, $matriks[$asal->value], true),
                    $asal->dapatBeralihKe($tujuan),
                    "Transisi keluhan {$asal->value} -> {$tujuan->value} menyimpang dari matriks.",
                );
            }
        }
    }

    public function test_hanya_ditutup_ditolak_dan_dibatalkan_yang_final_bagi_keluhan(): void
    {
        $final = array_values(array_filter(StatusKeluhan::cases(), fn (StatusKeluhan $s): bool => $s->final()));

        $this->assertSame([StatusKeluhan::Ditutup, StatusKeluhan::Ditolak, StatusKeluhan::Dibatalkan], $final);

        foreach ($final as $status) {
            $this->assertSame([], $status->tujuanYangDiizinkan(), "Status final {$status->value} tidak boleh punya jalan keluar.");
        }
    }

    public function test_setiap_pasangan_status_perintah_kerja_mengikuti_matriks_pengerjaan(): void
    {
        $matriks = [
            'Draf' => ['Terjadwal', 'Ditugaskan', 'Dibatalkan'],
            'Terjadwal' => ['Ditugaskan', 'Dibatalkan'],
            'Ditugaskan' => ['Diterima', 'Dibatalkan'],
            'Diterima' => ['Dikerjakan', 'Dibatalkan'],
            'Dikerjakan' => ['MenungguSukuCadang', 'MenungguPenyedia', 'Dijeda', 'MenungguVerifikasi'],
            'MenungguSukuCadang' => ['Dikerjakan', 'Dibatalkan'],
            'MenungguPenyedia' => ['Dikerjakan', 'Dibatalkan'],
            'Dijeda' => ['Dikerjakan', 'Dibatalkan'],
            'MenungguVerifikasi' => ['Selesai', 'Dikerjakan'],
            'Selesai' => ['Ditutup', 'Dikerjakan'],
            'Ditutup' => ['Dikerjakan'],
            'Dibatalkan' => [],
        ];

        $this->assertSame(array_keys($matriks), array_map(fn (StatusPerintahKerja $s): string => $s->value, StatusPerintahKerja::cases()));

        foreach (StatusPerintahKerja::cases() as $asal) {
            foreach (StatusPerintahKerja::cases() as $tujuan) {
                $this->assertSame(
                    in_array($tujuan->value, $matriks[$asal->value], true),
                    $asal->dapatBeralihKe($tujuan),
                    "Transisi perintah kerja {$asal->value} -> {$tujuan->value} menyimpang dari matriks.",
                );
            }
        }
    }

    /**
     * Waktu kerja, downtime, dan suku cadang hanya boleh dicatat selagi
     * pekerjaan berjalan; setelah verifikasi atau penutupan, catatan
     * operasional baru akan mengubah biaya yang sudah disahkan.
     */
    public function test_catatan_operasional_perintah_kerja_hanya_terbuka_selama_pengerjaan(): void
    {
        $terbuka = array_values(array_filter(
            StatusPerintahKerja::cases(),
            fn (StatusPerintahKerja $s): bool => $s->dapatMencatatOperasional(),
        ));

        $this->assertSame([
            StatusPerintahKerja::Diterima,
            StatusPerintahKerja::Dikerjakan,
            StatusPerintahKerja::MenungguSukuCadang,
            StatusPerintahKerja::MenungguPenyedia,
            StatusPerintahKerja::Dijeda,
        ], $terbuka);
    }
}
