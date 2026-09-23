<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * Kolom waktu berkas ekspor dicetak di jam dinding rumah sakit.
 *
 * Waktu disimpan UTC, dan pembaca berkas yang beredar di luar aplikasi tidak
 * punya cara tahu bahwa "17:30" di kolomnya adalah jam UTC. Tanggal kalender
 * (`date`) justru tidak boleh digeser: ia bukan momen.
 */
class KolomEksporZonaWaktuTest extends TestCase
{
    public function test_waktu_berjam_dicetak_di_zona_organisasi(): void
    {
        $sertifikat = new SertifikasiAset;
        $sertifikat->setRawAttributes(['DibuatPada' => '2026-09-21 17:30:00']);

        $kolom = KolomEkspor::tanggal('Dibuat', 'DibuatPada', 'Y-m-d H:i');

        $this->assertSame('2026-09-22 02:30', $kolom->nilai($sertifikat, 'Asia/Jayapura'));
        $this->assertSame('2026-09-22 00:30', $kolom->nilai($sertifikat, 'Asia/Jakarta'));
    }

    /**
     * Zona Indonesia di depan UTC tidak pernah memundurkan tengah malam UTC,
     * jadi pergeseran yang keliru hanya tampak pada zona di belakang UTC.
     * Aturannya tetap untuk zona apa pun: tanggal kalender dicetak apa adanya.
     */
    public function test_tanggal_kalender_tidak_digeser_oleh_zona(): void
    {
        $sertifikat = new SertifikasiAset;
        $sertifikat->TerbitPada = CarbonImmutable::parse('2026-09-22');

        $kolom = KolomEkspor::tanggal('Terbit', 'TerbitPada');

        $this->assertSame('2026-09-22', $kolom->nilai($sertifikat, 'Asia/Jayapura'));
        $this->assertSame('2026-09-22', $kolom->nilai($sertifikat, 'Pacific/Honolulu'));
    }
}
