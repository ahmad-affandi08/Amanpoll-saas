<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;

/** IDOR di dalam satu tenant (24). */
final class IdorDalamTenantTest extends KasusKeamanan
{
    private Organisasi $organisasi;

    private Pengguna $korban;

    private Pengguna $penyerang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = $this->buatOrganisasi('ORG-IDOR');
        $this->korban = $this->buatPengguna($this->organisasi);
        $this->penyerang = $this->buatPengguna($this->organisasi);
    }

    public function test_notifikasi_milik_pengguna_lain_tidak_dapat_ditandai_dibaca(): void
    {
        $notifikasi = $this->dalamOrganisasi($this->organisasi, fn (): Notifikasi => Notifikasi::create([
            'PenggunaId' => $this->korban->Id,
            'Kanal' => 'InApp',
            'JenisPeristiwa' => 'Uji.Peristiwa',
            'Judul' => 'Rahasia korban',
            'Isi' => 'Isi khusus korban.',
            'Status' => 'Terkirim',
            'JadwalKirimPada' => now(),
        ]));

        $this->actingAs($this->penyerang)
            ->post(route('notifikasi.baca', $notifikasi))
            ->assertForbidden();

        $masihBelumDibaca = $this->dalamOrganisasi(
            $this->organisasi,
            fn (): bool => Notifikasi::query()->whereKey($notifikasi->Id)->value('DibacaPada') === null,
        );

        $this->assertTrue($masihBelumDibaca);
    }

    public function test_pemilik_notifikasi_tetap_dapat_menandainya_dibaca(): void
    {
        $notifikasi = $this->dalamOrganisasi($this->organisasi, fn (): Notifikasi => Notifikasi::create([
            'PenggunaId' => $this->korban->Id,
            'Kanal' => 'InApp',
            'JenisPeristiwa' => 'Uji.Peristiwa',
            'Judul' => 'Milik sendiri',
            'Isi' => 'Isi.',
            'Status' => 'Terkirim',
            'JadwalKirimPada' => now(),
        ]));

        $this->actingAs($this->korban)
            ->post(route('notifikasi.baca', $notifikasi))
            ->assertRedirect();
    }

    public function test_perangkat_milik_pengguna_lain_tidak_dapat_dihapus(): void
    {
        $perangkat = $this->dalamOrganisasi($this->organisasi, fn (): PerangkatPengguna => PerangkatPengguna::create([
            'PenggunaId' => $this->korban->Id,
            'IdentitasPerangkat' => 'perangkat-korban',
            'NamaPerangkat' => 'Ponsel Korban',
            'Status' => 'Aktif',
        ]));

        $this->actingAs($this->penyerang)
            ->delete(route('platform.profil.perangkat.destroy', $perangkat))
            ->assertForbidden();

        $masihAda = $this->dalamOrganisasi(
            $this->organisasi,
            fn (): bool => PerangkatPengguna::query()->whereKey($perangkat->Id)->exists(),
        );

        $this->assertTrue($masihAda);
    }

    public function test_daftar_notifikasi_hanya_memuat_milik_sendiri(): void
    {
        $this->dalamOrganisasi($this->organisasi, function (): void {
            Notifikasi::create([
                'PenggunaId' => $this->korban->Id,
                'Kanal' => 'InApp',
                'JenisPeristiwa' => 'Uji.Peristiwa',
                'Judul' => 'Penanda Rahasia Korban',
                'Isi' => 'Isi khusus korban.',
                'Status' => 'Terkirim',
                'JadwalKirimPada' => now(),
            ]);
        });

        $respons = $this->actingAs($this->penyerang)->get(route('notifikasi.ringkasan'));

        $respons->assertOk();
        $this->assertStringNotContainsString('Penanda Rahasia Korban', $respons->getContent() ?: '');
    }
}
