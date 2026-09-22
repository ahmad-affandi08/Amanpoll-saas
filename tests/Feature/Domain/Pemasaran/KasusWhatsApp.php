<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\Enums\SumberKonsen;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\MenuWhatsAppPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateWhatsAppPemasaran;
use Tests\Dukungan\PenyediaWhatsAppPalsu;

/** Dasar test WhatsApp: penyedia palsu, satu prospek bernomor, dan satu template disetujui. */
abstract class KasusWhatsApp extends KasusPemasaran
{
    protected PenyediaWhatsAppPalsu $penyedia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->penyedia = new PenyediaWhatsAppPalsu;
        $this->app->instance(PenyediaWhatsApp::class, $this->penyedia);
    }

    protected function buatProspek(string $nomor = '081234567890', bool $setuju = true): Prospek
    {
        $prospek = Prospek::create([
            'Nama' => 'Budi Pabrik',
            'Email' => 'budi@pabrik.test',
            'WhatsApp' => $nomor,
            'Sumber' => SumberProspek::Website->value,
        ]);

        if ($setuju) {
            $this->setujui($nomor, $prospek);
        }

        return $prospek;
    }

    protected function setujui(string $nomor, ?Prospek $prospek = null): void
    {
        app(LayananKonsen::class)->catat(
            $nomor,
            true,
            SumberKonsen::Formulir,
            $prospek,
            kanal: KanalPesan::WhatsApp,
        );
    }

    protected function buatTemplate(
        StatusPersetujuanTemplateWa $status = StatusPersetujuanTemplateWa::Disetujui,
        string $kode = 'sapaan',
        bool $aktif = true,
    ): TemplateWhatsAppPemasaran {
        return TemplateWhatsAppPemasaran::create([
            'Kode' => $kode,
            'Nama' => 'Sapaan Awal',
            'Bahasa' => 'id',
            'Kategori' => 'Marketing',
            'IsiTeks' => 'Halo {{Nama}}, terima kasih sudah mampir.',
            'StatusPersetujuan' => $status,
            'Aktif' => $aktif,
        ]);
    }

    protected function buatMenu(): void
    {
        $butir = [
            ['1', 'Lihat Demo', 'Silakan buka tautan demo kami.'],
            ['2', 'Coba Gratis', 'Trial 14 hari tanpa kartu.'],
            ['3', 'Harga', 'Paket kami mulai dari Rp1.500.000 per bulan.'],
        ];

        foreach ($butir as $urutan => [$kunci, $label, $balasan]) {
            MenuWhatsAppPemasaran::create([
                'Kunci' => $kunci,
                'Urutan' => $urutan,
                'Label' => $label,
                'Balasan' => $balasan,
                'Aktif' => true,
            ]);
        }
    }
}
