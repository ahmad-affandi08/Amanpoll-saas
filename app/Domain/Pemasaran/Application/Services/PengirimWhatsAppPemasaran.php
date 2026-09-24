<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanWhatsAppPemasaran;
use Carbon\CarbonImmutable;
use Throwable;

/** Mengirim satu pesan WhatsApp; seluruh syarat bagian 16 diperiksa lagi di sini, bukan hanya saat dijadwalkan. */
final class PengirimWhatsAppPemasaran
{
    public function __construct(
        private readonly PenyediaWhatsApp $penyedia,
        private readonly LayananKonsen $konsen,
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
    ) {}

    public function kirim(PengirimanWhatsAppPemasaran $pengiriman): StatusPengirimanWhatsApp
    {
        if ($pengiriman->Status !== StatusPengirimanWhatsApp::Terjadwal) {
            return $pengiriman->Status;
        }

        // Orang mengirim STOP di jeda antara jadwal dan kirim, jadi consent ditanya lagi persis di sini.
        if (! $this->konsen->bolehDikirimi($pengiriman->Nomor, KanalPesan::WhatsApp)) {
            return $this->tandai(
                $pengiriman,
                StatusPengirimanWhatsApp::Unsubscribe,
                'Nomor tidak memiliki consent WhatsApp aktif.',
            );
        }

        $template = $pengiriman->template;

        if ($template === null) {
            return $this->tandai($pengiriman, StatusPengirimanWhatsApp::Gagal, 'Template tidak ada.');
        }

        // Persetujuan penyedia dapat dicabut setelah pesan dijadwalkan.
        if (! $template->siapKirim()) {
            return $this->tandai(
                $pengiriman,
                StatusPengirimanWhatsApp::Ditolak,
                "Template {$template->Kode} tidak disetujui penyedia.",
            );
        }

        if ($this->melampauiCap($pengiriman)) {
            return $this->tandai(
                $pengiriman,
                StatusPengirimanWhatsApp::Ditolak,
                'Nomor sudah mencapai batas pesan pada jendela waktu ini.',
            );
        }

        $pengiriman->Percobaan++;

        try {
            $idPesan = $this->penyedia->kirim(new PesanWhatsApp(
                kepada: $pengiriman->Nomor,
                kodeTemplate: $template->Kode,
                bahasa: $template->Bahasa,
                isiTeks: $pengiriman->IsiTeks,
                idTemplatePenyedia: $template->IdTemplatePenyedia,
                naskahTemplate: $template->IsiTeks,
            ));
        } catch (Throwable $galat) {
            $pengiriman->Galat = mb_substr($galat->getMessage(), 0, 500);
            $pengiriman->save();

            throw $galat;
        }

        $pengiriman->IdPesanPenyedia = $idPesan;
        $pengiriman->DikirimPada = CarbonImmutable::now();

        return $this->tandai($pengiriman, StatusPengirimanWhatsApp::Dikirim);
    }

    /** Status hanya boleh maju; kabar yang datang terlambat tidak memundurkan yang sudah lebih jauh. */
    public function perbaruiStatus(
        PengirimanWhatsAppPemasaran $pengiriman,
        StatusPengirimanWhatsApp $status,
        ?string $keterangan = null,
    ): StatusPengirimanWhatsApp {
        if (! $status->lebihMajuDari($pengiriman->Status)) {
            return $pengiriman->Status;
        }

        return $this->tandai($pengiriman, $status, $keterangan);
    }

    /** Berapa pesan yang sudah benar-benar berangkat ke nomor ini di dalam jendela capnya. */
    public function terkirimDalamJendela(string $nomor, ?CarbonImmutable $pada = null): int
    {
        $sekarang = $pada ?? CarbonImmutable::now();
        $jendela = $sekarang->subHours($this->jendelaJam());

        return PengirimanWhatsAppPemasaran::query()
            ->where('Nomor', KanalPesan::WhatsApp->normalkan($nomor))
            ->whereNotNull('DikirimPada')
            ->where('DikirimPada', '>=', $jendela)
            ->whereIn('Status', $this->statusTerkirim())
            ->count();
    }

    private function melampauiCap(PengirimanWhatsAppPemasaran $pengiriman): bool
    {
        $cap = (int) $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::WHATSAPP_CAP_PER_NOMOR);

        return $cap > 0 && $this->terkirimDalamJendela($pengiriman->Nomor) >= $cap;
    }

    private function jendelaJam(): int
    {
        return max((int) $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::WHATSAPP_CAP_JENDELA_JAM), 1);
    }

    /** @return list<string> */
    private function statusTerkirim(): array
    {
        return array_values(array_map(
            fn (StatusPengirimanWhatsApp $satu): string => $satu->value,
            array_filter(
                StatusPengirimanWhatsApp::cases(),
                fn (StatusPengirimanWhatsApp $satu): bool => $satu->sudahDikirim(),
            ),
        ));
    }

    private function tandai(
        PengirimanWhatsAppPemasaran $pengiriman,
        StatusPengirimanWhatsApp $status,
        ?string $keterangan = null,
    ): StatusPengirimanWhatsApp {
        $pengiriman->Status = $status;
        $pengiriman->DiperbaruiStatusPada = CarbonImmutable::now();

        if ($keterangan !== null) {
            $pengiriman->Galat = mb_substr($keterangan, 0, 500);
        }

        $pengiriman->save();

        return $status;
    }
}
