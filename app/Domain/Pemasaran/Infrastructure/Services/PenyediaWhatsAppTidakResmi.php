<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Services;

use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanWhatsApp;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;

/**
 * Dasar gateway WhatsApp tidak resmi: WhatsApp biasa yang disambungkan lewat scan QR.
 *
 * Tidak ada peninjauan Meta, jadi template tidak pernah menunggu persetujuan dan pesan
 * berangkat sebagai teks biasa yang sudah dirender. Webhooknya tidak bertanda tangan,
 * sehingga keasliannya hanya dijaga token rahasia di URL.
 */
abstract class PenyediaWhatsAppTidakResmi extends PenyediaWhatsAppHttp
{
    final public function resmi(): bool
    {
        return false;
    }

    public function mendukungModeUji(): bool
    {
        return false;
    }

    final public function keterangan(): string
    {
        return $this->ringkasan().' Tidak resmi: melanggar ketentuan layanan WhatsApp dan nomornya bisa diblokir WhatsApp sewaktu-waktu, jadi jangan pakai nomor utama perusahaan.';
    }

    /** Satu kalimat tentang penyedianya sendiri; peringatan risikonya ditambahkan kelas dasar. */
    abstract protected function ringkasan(): string;

    /** Mengirim teks biasa dan mengembalikan pengenal pesan di sisi penyedia. */
    abstract protected function kirimTeks(KredensialPenyedia $kredensial, string $nomor, string $teks): string;

    /** @return list<IsianKredensial> */
    abstract protected function isianPenyedia(): array;

    /** @return list<IsianKredensial> */
    final public function isian(): array
    {
        return [
            ...$this->isianPenyedia(),
            new IsianKredensial(
                'TokenWebhook',
                'Token webhook',
                rahasia: true,
                wajib: false,
                petunjuk: 'Teks acak buatan Anda. Tambahkan ?token=<nilai ini> di akhir URL webhook yang didaftarkan di dashboard penyedia; kosongkan bila webhook tidak dipakai.',
                hanyaPlatform: true,
            ),
        ];
    }

    public function kirim(PesanWhatsApp $pesan): string
    {
        return $this->kirimTeks($this->kredensial(), $this->nomor($pesan->kepada), $pesan->isiTeks);
    }

    public function balas(string $kepada, string $teks): string
    {
        return $this->kirimTeks($this->kredensial(), $this->nomor($kepada), $teks);
    }

    /** Tanpa template Meta, notifikasi berangkat sebagai teks biasa dengan judul bercetak tebal. */
    public function kirimNotifikasi(string $nomor, string $judul, string $isi): string
    {
        return $this->kirimNotifikasiDengan($this->kredensial(), $nomor, $judul, $isi);
    }

    public function kirimNotifikasiDengan(KredensialPenyedia $kredensial, string $nomor, string $judul, string $isi): string
    {
        $teks = trim($judul) === '' ? $isi : '*'.trim($judul)."*\n".$isi;

        return $this->kirimTeks($kredensial, $this->nomor($nomor), $teks);
    }

    public function ajukanTemplate(
        string $kode,
        string $bahasa,
        string $kategori,
        string $isiTeks,
    ): PersetujuanTemplateWa {
        return $this->tanpaPeninjauan();
    }

    public function periksaTemplate(string $kode): PersetujuanTemplateWa
    {
        return $this->tanpaPeninjauan();
    }

    /** @param array<string, mixed> $kueri */
    public function verifikasiLangganan(array $kueri): ?string
    {
        return null;
    }

    /**
     * @param  array<string, string>  $header
     * @param  array<string, mixed>  $kueri
     */
    public function webhookSah(string $badanMentah, array $header, array $kueri): bool
    {
        $harapan = $this->kredensialAtauKosong()?->ambilAtau('TokenWebhook') ?? '';
        $diberikan = $kueri['token'] ?? null;

        // Tanpa token yang diatur, webhook ditolak: menerima semua kiriman berarti siapa pun dapat mencabut consent orang.
        return $harapan !== '' && is_string($diberikan) && hash_equals($harapan, $diberikan);
    }

    private function tanpaPeninjauan(): PersetujuanTemplateWa
    {
        return new PersetujuanTemplateWa(
            StatusPersetujuanTemplateWa::Disetujui,
            alasan: "Disetujui otomatis: {$this->nama()} adalah gateway tidak resmi tanpa peninjauan template oleh Meta; naskah dikirim sebagai teks biasa.",
        );
    }
}
