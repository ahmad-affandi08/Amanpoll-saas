<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Keamanan;

/**
 * Resolusi DNS memakai resolver sistem.
 *
 * `dns_get_record` membaca A dan AAAA langsung dari DNS, sedangkan
 * `gethostbynamel` ikut membaca `/etc/hosts`. Keduanya digabung karena
 * penjaga harus menolak bila SALAH SATU alamat yang mungkin dipakai
 * sambungan mengarah ke jaringan internal.
 */
final class PenyelesaiDnsSistem implements PenyelesaiDns
{
    /** @return list<string> */
    public function alamat(string $host): array
    {
        $alamat = [];

        $rekaman = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (is_array($rekaman)) {
            foreach ($rekaman as $baris) {
                $ip = $baris['ip'] ?? $baris['ipv6'] ?? null;
                if (is_string($ip) && $ip !== '') {
                    $alamat[] = $ip;
                }
            }
        }

        $ipv4 = @gethostbynamel($host);
        if (is_array($ipv4)) {
            $alamat = [...$alamat, ...$ipv4];
        }

        return array_values(array_unique($alamat));
    }
}
