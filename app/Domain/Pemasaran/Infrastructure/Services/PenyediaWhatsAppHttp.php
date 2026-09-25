<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Services;

use App\Domain\Notifikasi\Domain\Contracts\DapatMengirimNotifikasiWhatsApp;
use App\Domain\Pemasaran\Domain\Contracts\DapatMembalasWhatsApp;
use App\Domain\Pemasaran\Domain\Contracts\PenerimaWebhookWhatsApp;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanWhatsApp;
use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Domain\Contracts\DapatDiujiKoneksi;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

/**
 * Dasar adapter WhatsApp yang memanggil penyedia lewat HTTP dengan kredensial dari konsol platform.
 *
 * Kredensial dibaca saat dipakai, bukan saat dibuat, supaya adapter tetap dapat di-resolve
 * (mis. oleh katalog konsol) sebelum penyedianya diatur. Nilai isian rahasia disensor dari
 * setiap pesan galat, karena pesan itu berakhir di kolom `Galat`, log, dan layar operator.
 */
abstract class PenyediaWhatsAppHttp implements DapatDiujiKoneksi, DapatMembalasWhatsApp, DapatMengirimNotifikasiWhatsApp, DeskripsiPenyediaLayanan, PenerimaWebhookWhatsApp, PenyediaWhatsApp
{
    protected const BATAS_WAKTU_DETIK = 15;

    public function __construct(private readonly PembacaKredensialPenyedia $pembaca) {}

    final public function kategori(): KategoriPenyediaLayanan
    {
        return KategoriPenyediaLayanan::WhatsApp;
    }

    /**
     * Status kiriman datang lewat webhook; tidak satu pun penyedia ini punya tarikan status yang murah.
     *
     * @param  list<string>  $idPesan
     * @return list<StatusKirimanWhatsApp>
     */
    public function statusKiriman(array $idPesan): array
    {
        return [];
    }

    final public function ujiKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        try {
            return $this->periksaKoneksi($kredensial);
        } catch (AturanBisnisDilanggar $galat) {
            return new HasilUjiKoneksi(false, $galat->getMessage());
        }
    }

    abstract protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi;

    /** Pesan galat di dalam jawaban penyedia; tiap penyedia menaruhnya di tempat berbeda. */
    abstract protected function pesanGalat(Response $jawaban): string;

    protected function kredensial(): KredensialPenyedia
    {
        return $this->kredensialAtauKosong() ?? throw new AturanBisnisDilanggar(
            "Penyedia WhatsApp {$this->nama()} belum diatur atau belum diaktifkan di konsol platform.",
        );
    }

    protected function kredensialAtauKosong(): ?KredensialPenyedia
    {
        return $this->pembaca->untuk(KategoriPenyediaLayanan::WhatsApp, $this->kode());
    }

    /**
     * Menjalankan satu panggilan HTTP dan menerjemahkan kegagalannya menjadi pesan Indonesia.
     *
     * @param  Closure(): Response  $panggilan
     */
    protected function panggil(KredensialPenyedia $kredensial, string $tindakan, Closure $panggilan): Response
    {
        try {
            $jawaban = $panggilan();
        } catch (ConnectionException) {
            // Pesan aslinya memuat URL lengkap, yang pada sebagian penyedia membawa token.
            throw new AturanBisnisDilanggar(
                "Tidak dapat menghubungi {$this->nama()} saat {$tindakan}. Periksa alamat server dan jaringan, lalu coba lagi.",
            );
        }

        if ($jawaban->failed()) {
            throw $this->galat($kredensial, $tindakan, $jawaban);
        }

        return $jawaban;
    }

    protected function galat(
        KredensialPenyedia $kredensial,
        string $tindakan,
        Response $jawaban,
        ?string $pesan = null,
    ): AturanBisnisDilanggar {
        $rincian = $this->sensor($kredensial, $pesan ?? $this->pesanGalat($jawaban));
        $kepala = "{$this->nama()} menolak {$tindakan} (HTTP {$jawaban->status()})";

        return new AturanBisnisDilanggar($rincian === '' ? "{$kepala}." : "{$kepala}: {$rincian}");
    }

    /** Membuang setiap nilai isian rahasia dari teks, lalu memendekkannya. */
    protected function sensor(KredensialPenyedia $kredensial, string $teks): string
    {
        return $kredensial->sensor($teks, $this->isian(), 200);
    }

    protected function nomor(string $kepada): string
    {
        return KanalPesan::WhatsApp->normalkan($kepada);
    }

    /** Membaca satu nilai teks dari jawaban JSON tanpa melempar bila bentuknya tak terduga. */
    protected function teksDari(Response $jawaban, string $kunci): string
    {
        $nilai = $jawaban->json($kunci);

        return is_scalar($nilai) ? trim((string) $nilai) : '';
    }

    /**
     * @param  array<mixed>  $data
     */
    protected function teksDalam(array $data, string $kunci): string
    {
        $nilai = data_get($data, $kunci);

        return is_scalar($nilai) ? trim((string) $nilai) : '';
    }
}
