<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Contracts;

use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Penyedia pengirim email yang dipilih di konsol platform (PRD 8.23).
 *
 * Seluruh email aplikasi lewat mailer Laravel `amanpoll`; mailer itu meminta
 * penyedia aktif membangun transport dari kredensial tersimpannya saat mengirim.
 */
interface PenyediaEmail
{
    /** Isian kredensial alamat pengirim; wajib di setiap penyedia email. */
    public const ISIAN_ALAMAT_PENGIRIM = 'AlamatPengirim';

    /** Isian kredensial nama pengirim; wajib di setiap penyedia email. */
    public const ISIAN_NAMA_PENGIRIM = 'NamaPengirim';

    public function buatTransport(KredensialPenyedia $kredensial): TransportInterface;
}
