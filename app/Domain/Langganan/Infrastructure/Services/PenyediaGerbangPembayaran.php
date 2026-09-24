<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Services;

use App\Domain\Langganan\Domain\Contracts\PenyediaPembayaran;
use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dasar bersama adapter payment gateway (PRD 8.23).
 *
 * Kredensial dibaca dari konsol platform setiap kali dipakai. Kegagalan HTTP
 * diterjemahkan ke pesan Indonesia yang hanya menyebut kode status: badan jawaban
 * penyedia tidak pernah diteruskan ke pengguna atau log karena sebagian penyedia
 * menggemakan kembali isi permintaan, termasuk header otorisasinya.
 */
abstract class PenyediaGerbangPembayaran implements DeskripsiPenyediaLayanan, PenyediaPembayaran
{
    protected const BATAS_WAKTU_DETIK = 15;

    public function __construct(protected readonly PembacaKredensialPenyedia $pembaca) {}

    public function kategori(): KategoriPenyediaLayanan
    {
        return KategoriPenyediaLayanan::Pembayaran;
    }

    public function resmi(): bool
    {
        return true;
    }

    public function mendukungModeUji(): bool
    {
        return true;
    }

    protected function kredensial(): KredensialPenyedia
    {
        return $this->kredensialAtauKosong()
            ?? throw new AturanBisnisDilanggar("{$this->nama()} belum diatur di konsol platform.");
    }

    /** Webhook memakai varian ini: penyedia yang belum diatur menolak semua kiriman (gagal-tertutup). */
    protected function kredensialAtauKosong(): ?KredensialPenyedia
    {
        return $this->pembaca->untuk(KategoriPenyediaLayanan::Pembayaran, $this->kode());
    }

    protected function http(): PendingRequest
    {
        return Http::timeout(self::BATAS_WAKTU_DETIK)->acceptJson();
    }

    /** @param  Closure(PendingRequest): Response  $permintaan */
    protected function panggil(Closure $permintaan): Response
    {
        try {
            $respons = $permintaan($this->http());
        } catch (ConnectionException) {
            throw new AturanBisnisDilanggar("Tidak dapat menghubungi {$this->nama()}. Coba beberapa saat lagi.");
        }

        if ($respons->failed()) {
            Log::warning('Penyedia pembayaran menolak permintaan.', [
                'Penyedia' => $this->kode(),
                'StatusHttp' => $respons->status(),
            ]);

            throw new AturanBisnisDilanggar(
                "{$this->nama()} menolak permintaan pembayaran (HTTP {$respons->status()}). Periksa pengaturan di konsol platform.",
            );
        }

        return $respons;
    }

    /**
     * @param  Closure(PendingRequest): Response  $permintaan
     * @param  (Closure(Response): bool)|null  $berhasil
     */
    protected function uji(Closure $permintaan, ?Closure $berhasil = null): HasilUjiKoneksi
    {
        try {
            $respons = $permintaan($this->http());
        } catch (ConnectionException) {
            return new HasilUjiKoneksi(false, "Tidak dapat menghubungi {$this->nama()}.");
        }

        if (in_array($respons->status(), [401, 403], true)) {
            return new HasilUjiKoneksi(false, "{$this->nama()} menolak kredensial ini (HTTP {$respons->status()}).");
        }

        $sah = $berhasil !== null ? $berhasil($respons) : $respons->successful();

        return $sah
            ? new HasilUjiKoneksi(true, "Kredensial {$this->nama()} diterima.")
            : new HasilUjiKoneksi(false, "{$this->nama()} menjawab tidak terduga (HTTP {$respons->status()}).");
    }

    /** Nilai skalar dari muatan penyedia sebagai teks; selain skalar dianggap kosong. */
    protected static function teks(mixed $nilai): string
    {
        return is_scalar($nilai) ? trim((string) $nilai) : '';
    }

    protected static function angka(mixed $nilai): float
    {
        return is_numeric($nilai) ? (float) $nilai : 0.0;
    }

    /**
     * Muatan JSON sebagai larik; badan yang bukan objek JSON menjadi larik kosong.
     *
     * @return array<string, mixed>
     */
    protected static function muatanJson(string $badan): array
    {
        $data = json_decode($badan, true);

        if (! is_array($data)) {
            return [];
        }

        $hasil = [];
        foreach ($data as $kunci => $nilai) {
            $hasil[(string) $kunci] = $nilai;
        }

        return $hasil;
    }

    /**
     * Muatan form-urlencoded (Duitku, iPaymu) sebagai larik berkunci teks.
     *
     * @return array<string, mixed>
     */
    protected static function muatanForm(Request $permintaan): array
    {
        $hasil = [];
        foreach ($permintaan->request->all() as $kunci => $nilai) {
            $hasil[(string) $kunci] = $nilai;
        }

        return $hasil;
    }

    /**
     * Mengambil nilai bersarang dengan notasi titik dari muatan penyedia.
     *
     * @param  array<string, mixed>  $muatan
     */
    protected static function ambil(array $muatan, string $jalur): mixed
    {
        return data_get($muatan, $jalur);
    }

    /** Menolak mencampur kunci produksi dengan mode uji (dan sebaliknya) untuk penyedia yang modenya ditentukan kunci. */
    protected function pastikanKunciSesuaiMode(KredensialPenyedia $kredensial, string $kunci, string $penandaUji, string $penandaProduksi): void
    {
        $nilai = $kredensial->ambil($kunci);

        if ($kredensial->modeUji && str_contains($nilai, $penandaProduksi)) {
            throw new AturanBisnisDilanggar("{$this->nama()} dalam mode uji, tetapi kunci yang tersimpan adalah kunci produksi.");
        }

        if (! $kredensial->modeUji && str_contains($nilai, $penandaUji)) {
            throw new AturanBisnisDilanggar("{$this->nama()} dalam mode produksi, tetapi kunci yang tersimpan adalah kunci uji.");
        }
    }
}
