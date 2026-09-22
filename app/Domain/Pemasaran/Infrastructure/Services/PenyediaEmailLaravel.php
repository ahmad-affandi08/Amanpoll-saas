<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Services;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaEmailPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanEmail;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanPenyedia;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Penyedia bawaan: mailer Laravel yang sudah dikonfigurasi aplikasi.
 *
 * Tidak dapat melaporkan status kiriman — SMTP tidak tahu apakah suratnya dibuka
 * — jadi statusKiriman() mengembalikan daftar kosong, dan penyinkron status
 * tidak akan pernah memundurkan apa pun karenanya.
 */
final class PenyediaEmailLaravel implements PenyediaEmailPemasaran
{
    public function kode(): string
    {
        return 'Laravel';
    }

    public function kirim(PesanEmail $pesan): string
    {
        Mail::html($this->denganTautanBerhenti($pesan), function ($surat) use ($pesan): void {
            $surat->to($pesan->kepada)->subject($pesan->subjek);
        });

        return (string) Str::ulid();
    }

    /**
     * @param  list<string>  $idPesan
     * @return list<StatusKirimanPenyedia>
     */
    public function statusKiriman(array $idPesan): array
    {
        return [];
    }

    /** Tautan berhenti langganan wajib ada di badan surat, bukan hanya di header. */
    private function denganTautanBerhenti(PesanEmail $pesan): string
    {
        if ($pesan->urlBerhentiLangganan === null) {
            return $pesan->isiHtml;
        }

        return $pesan->isiHtml
            .'<hr><p style="font-size:12px;color:#667">'
            .'<a href="'.e($pesan->urlBerhentiLangganan).'">Berhenti berlangganan</a>'
            .'</p>';
    }
}
