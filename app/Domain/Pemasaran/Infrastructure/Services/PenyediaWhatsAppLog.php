<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Services;

use App\Domain\Notifikasi\Domain\Contracts\DapatMengirimNotifikasiWhatsApp;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanWhatsApp;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Domain\ValueObjects\NomorWhatsApp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Penyedia bawaan yang hanya menulis ke log: akun WhatsApp Business belum ada. */
final class PenyediaWhatsAppLog implements DapatMengirimNotifikasiWhatsApp, PenyediaWhatsApp
{
    // Ia tidak pernah menyetujui template sendiri; itu berarti berbohong atas nama penyedia.
    public function kode(): string
    {
        return 'Log';
    }

    public function kirim(PesanWhatsApp $pesan): string
    {
        Log::info('Pesan WhatsApp ditulis ke log, bukan dikirim.', [
            'Kepada' => $pesan->kepada,
            'Template' => $pesan->kodeTemplate,
            'Bahasa' => $pesan->bahasa,
        ]);

        return (string) Str::ulid();
    }

    /** Nomor staf disamarkan: log dibaca lebih banyak orang daripada tabel notifikasi. */
    public function kirimNotifikasi(string $nomor, string $judul, string $isi): string
    {
        Log::info('Notifikasi WhatsApp ditulis ke log, bukan dikirim.', [
            'Kepada' => NomorWhatsApp::samarkan($nomor),
            'Judul' => $judul,
        ]);

        return (string) Str::ulid();
    }

    /** Log tidak memanggil siapa pun, jadi kredensial yang diberikan tidak dipakai. */
    public function kirimNotifikasiDengan(KredensialPenyedia $kredensial, string $nomor, string $judul, string $isi): string
    {
        return $this->kirimNotifikasi($nomor, $judul, $isi);
    }

    /**
     * @param  list<string>  $idPesan
     * @return list<StatusKirimanWhatsApp>
     */
    public function statusKiriman(array $idPesan): array
    {
        return [];
    }

    public function ajukanTemplate(
        string $kode,
        string $bahasa,
        string $kategori,
        string $isiTeks,
    ): PersetujuanTemplateWa {
        Log::info('Pengajuan template WhatsApp ditulis ke log, bukan dikirim.', ['Kode' => $kode]);

        return new PersetujuanTemplateWa(
            StatusPersetujuanTemplateWa::Diajukan,
            alasan: 'Penyedia log tidak menghubungi Meta; persetujuan harus dicatat manual.',
        );
    }

    public function periksaTemplate(string $kode): PersetujuanTemplateWa
    {
        return new PersetujuanTemplateWa(
            StatusPersetujuanTemplateWa::Diajukan,
            alasan: 'Penyedia log tidak punya kabar dari Meta.',
        );
    }
}
