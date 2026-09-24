<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Keamanan;

use App\Shared\Domain\Exceptions\PengecualianDomain;

/**
 * URL tujuan panggilan keluar ditolak `PenjagaUrlKeluar`.
 *
 * Pesannya aman ditampilkan ke pengguna: tidak pernah memuat alamat IP hasil
 * resolusi, supaya penolakan tidak menjadi alat memetakan jaringan internal.
 */
final class UrlKeluarDitolak extends PengecualianDomain
{
    public const PESAN_JARINGAN_INTERNAL = 'Alamat ini mengarah ke jaringan internal dan tidak diizinkan.';

    /** @param  bool  $sementara  Kegagalan yang mungkin pulih sendiri (host belum dapat di-resolve), layak dicoba ulang. */
    public function __construct(string $pesan, public readonly bool $sementara = false)
    {
        parent::__construct($pesan);
    }

    public function kodeStatusHttp(): int
    {
        return 422;
    }

    public function kodeError(): string
    {
        return 'URL_KELUAR_DITOLAK';
    }
}
