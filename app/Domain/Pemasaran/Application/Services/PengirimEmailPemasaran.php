<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaEmailPemasaran;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use Carbon\CarbonImmutable;
use Throwable;

/** Mengirim satu email; consent diperiksa lagi di sini karena orang berhenti langganan di jeda antara jadwal dan kirim (MARKETING.md 15, 27). */
final class PengirimEmailPemasaran
{
    public function __construct(
        private readonly PenyediaEmailPemasaran $penyedia,
        private readonly LayananKonsen $konsen,
        private readonly PerenderTemplateEmail $perender,
        private readonly PenautBerhentiLangganan $penaut,
    ) {}

    public function kirim(PengirimanEmailPemasaran $pengiriman): StatusPengirimanEmail
    {
        if ($pengiriman->Status !== StatusPengirimanEmail::Terjadwal) {
            return $pengiriman->Status;
        }

        if (! $this->konsen->bolehDikirimi($pengiriman->Email)) {
            return $this->tandai($pengiriman, StatusPengirimanEmail::Unsubscribe, 'Tidak memiliki consent aktif.');
        }

        $prospek = $pengiriman->prospek;
        $template = $pengiriman->template;

        if ($prospek === null || $template === null) {
            return $this->tandai($pengiriman, StatusPengirimanEmail::Gagal, 'Prospek atau template tidak ada.');
        }

        $dirender = $this->perender->render($template, $prospek);
        $pengiriman->Percobaan++;

        try {
            $idPesan = $this->penyedia->kirim(new PesanEmail(
                kepada: $pengiriman->Email,
                subjek: $dirender['Subjek'],
                isiHtml: $dirender['IsiHtml'],
                isiTeks: $dirender['IsiTeks'],
                urlBerhentiLangganan: $this->penaut->untuk($pengiriman),
            ));
        } catch (Throwable $galat) {
            $pengiriman->Galat = mb_substr($galat->getMessage(), 0, 500);
            $pengiriman->save();

            throw $galat;
        }

        $pengiriman->IdPesanPenyedia = $idPesan;
        $pengiriman->Subjek = $dirender['Subjek'];
        $pengiriman->DikirimPada = CarbonImmutable::now();

        return $this->tandai($pengiriman, StatusPengirimanEmail::Dikirim);
    }

    /** Status hanya boleh maju; kabar yang datang terlambat tidak memundurkan yang sudah lebih jauh. */
    public function perbaruiStatus(
        PengirimanEmailPemasaran $pengiriman,
        StatusPengirimanEmail $status,
        ?string $keterangan = null,
    ): StatusPengirimanEmail {
        if (! $status->lebihMajuDari($pengiriman->Status)) {
            return $pengiriman->Status;
        }

        // Alamat yang memantul akan memantul lagi, dan reputasi pengirimlah yang membayarnya.
        if ($status === StatusPengirimanEmail::Bounce) {
            $this->konsen->supresi($pengiriman->Email, AlasanSupresi::Bounce, $keterangan);
        }

        return $this->tandai($pengiriman, $status, $keterangan);
    }

    private function tandai(
        PengirimanEmailPemasaran $pengiriman,
        StatusPengirimanEmail $status,
        ?string $keterangan = null,
    ): StatusPengirimanEmail {
        $pengiriman->Status = $status;
        $pengiriman->DiperbaruiStatusPada = CarbonImmutable::now();

        if ($keterangan !== null) {
            $pengiriman->Galat = mb_substr($keterangan, 0, 500);
        }

        $pengiriman->save();

        return $status;
    }
}
