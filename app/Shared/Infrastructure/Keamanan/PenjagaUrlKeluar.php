<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Keamanan;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;

/**
 * Satu pintu untuk setiap panggilan HTTP keluar yang URL-nya ditulis pengguna
 * (webhook tenant, integrasi eksternal, server WhatsApp isian admin).
 *
 * Tanpa penjaga ini, URL isian menjadi SSRF: server Amanpoll bisa disuruh
 * memanggil 127.0.0.1, jaringan hosting, atau metadata awan, dan pada webhook
 * isi balasannya bahkan terbaca kembali oleh tenant.
 *
 * Aturannya:
 * - skema `https` saja; `http` hanya bila `amanpoll.http_keluar.izinkan_http`;
 * - tanpa userinfo, port 80/443 atau yang didaftarkan di `port_tambahan`;
 * - SELURUH alamat hasil resolusi harus publik (daftar CIDR eksplisit
 *   ditambah `filter_var`, yang sendirian tidak menutup CGNAT, dokumentasi,
 *   IPv4-mapped IPv6, dan sejenisnya);
 * - alamat yang diperiksa disematkan ke sambungan (`CURLOPT_RESOLVE`) sehingga
 *   DNS rebinding tidak dapat membelokkan tujuan sesudah pemeriksaan;
 * - redirect tidak diikuti sama sekali (lihat `klien()`).
 *
 * Validasi saat menyimpan (`UrlKeluarPublik`) hanya memberi pesan lebih awal;
 * pemeriksaan yang mengikat adalah yang dijalankan tepat sebelum mengirim.
 */
final class PenjagaUrlKeluar
{
    public const PESAN_DITOLAK_SAAT_KIRIM = 'Alamat tujuan ditolak penjaga jaringan';

    /** Rentang IPv4 yang tidak boleh dituju, termasuk yang lolos dari `filter_var`. */
    private const BLOK_IPV4 = [
        '0.0.0.0/8',          // "jaringan ini"; 0.0.0.0 di Linux berarti localhost
        '10.0.0.0/8',         // privat
        '100.64.0.0/10',      // CGNAT, lazim dipakai jaringan internal hosting
        '127.0.0.0/8',        // loopback
        '169.254.0.0/16',     // link-local, termasuk metadata awan 169.254.169.254
        '172.16.0.0/12',      // privat
        '192.0.0.0/24',       // penugasan protokol IETF
        '192.0.2.0/24',       // dokumentasi TEST-NET-1
        '192.88.99.0/24',     // relay 6to4 (usang)
        '192.168.0.0/16',     // privat
        '198.18.0.0/15',      // uji tolok ukur
        '198.51.100.0/24',    // dokumentasi TEST-NET-2
        '203.0.113.0/24',     // dokumentasi TEST-NET-3
        '224.0.0.0/4',        // multicast
        '240.0.0.0/4',        // cadangan, termasuk 255.255.255.255
    ];

    /** Rentang IPv6 yang tidak boleh dituju. IPv4 tertanam diperiksa terpisah. */
    private const BLOK_IPV6 = [
        '::/96',              // tak-tertentu, loopback ::1, dan IPv4-compatible usang
        '64:ff9b:1::/48',     // NAT64 lokal
        '100::/64',           // discard
        '2001::/23',          // penugasan protokol IETF, termasuk Teredo 2001::/32
        '2001:db8::/32',      // dokumentasi
        '3fff::/20',          // dokumentasi
        'fc00::/7',           // ULA, termasuk metadata AWS fd00:ec2::254
        'fe80::/10',          // link-local
        'fec0::/10',          // site-local usang
        'ff00::/8',           // multicast
    ];

    /** Awalan IPv6 yang membawa IPv4 di dalamnya: [awalan CIDR, posisi byte IPv4]. */
    private const IPV4_TERTANAM = [
        ['::ffff:0:0/96', 12], // IPv4-mapped
        ['64:ff9b::/96', 12],  // NAT64 terkenal
        ['2002::/16', 2],      // 6to4
    ];

    /** Akhiran nama yang selalu menunjuk jaringan lokal. */
    private const AKHIRAN_LOKAL = ['localhost', 'local', 'internal', 'localdomain', 'home.arpa'];

    public function __construct(private readonly PenyelesaiDns $dns) {}

    /**
     * Memeriksa URL dan meresolusinya. Dijalankan lagi setiap kali akan mengirim,
     * karena jawaban DNS dapat berubah sejak URL disimpan.
     *
     * @throws UrlKeluarDitolak
     */
    public function periksa(string $url): UrlKeluarSah
    {
        $url = trim($url);

        // Hanya ASCII tampak: spasi, karakter kendali, huruf non-ASCII, dan garis miring
        // terbalik adalah bahan klasik untuk membuat parse_url dan curl berbeda pendapat.
        if ($url === '' || strlen($url) > 2000 || preg_match('/[^\x21-\x7E]/', $url) === 1 || str_contains($url, '\\')) {
            throw new UrlKeluarDitolak('Alamat tidak valid. Tulis alamat lengkap tanpa spasi, mis. https://contoh.co.id/webhook.');
        }

        $bagian = parse_url($url);
        if (! is_array($bagian) || ! isset($bagian['scheme'], $bagian['host'])) {
            throw new UrlKeluarDitolak('Alamat tidak valid. Tulis alamat lengkap, mis. https://contoh.co.id/webhook.');
        }

        $skema = strtolower($bagian['scheme']);
        $izinkanHttp = (bool) config('amanpoll.http_keluar.izinkan_http', false);
        if ($skema !== 'https' && ! ($skema === 'http' && $izinkanHttp)) {
            throw new UrlKeluarDitolak($izinkanHttp
                ? 'Alamat harus diawali http:// atau https://.'
                : 'Alamat harus memakai https://.');
        }

        $otoritas = $this->otoritas($url, $skema);
        if (isset($bagian['user']) || isset($bagian['pass']) || str_contains($otoritas, '@')) {
            throw new UrlKeluarDitolak('Alamat tidak boleh memuat nama pengguna atau kata sandi.');
        }

        $hostAsli = strtolower($bagian['host']);
        $portTertulis = $bagian['port'] ?? null;

        // Otoritas mentah harus persis host[:port] hasil parse_url; bila tidak, ada
        // bagian yang ditafsirkan berbeda dan URL itu tidak dipercaya.
        if ($otoritas !== $hostAsli && $otoritas !== "{$hostAsli}:{$portTertulis}") {
            throw new UrlKeluarDitolak('Alamat tidak valid. Tulis alamat lengkap, mis. https://contoh.co.id/webhook.');
        }

        $port = $portTertulis ?? ($skema === 'https' ? 443 : 80);
        if (! in_array($port, $this->portDiizinkan(), true)) {
            throw new UrlKeluarDitolak("Port {$port} tidak diizinkan. Pakai port bawaan (443 untuk https).");
        }

        [$host, $daftarAlamat, $hostBerupaIp] = $this->resolusi($hostAsli);

        foreach ($daftarAlamat as $alamat) {
            if ($this->alamatTerlarang($alamat)) {
                throw new UrlKeluarDitolak(UrlKeluarDitolak::PESAN_JARINGAN_INTERNAL);
            }
        }

        $urlBaku = $skema.'://'.$host
            .($portTertulis !== null ? ':'.$portTertulis : '')
            .($bagian['path'] ?? '')
            .(isset($bagian['query']) ? '?'.$bagian['query'] : '');

        return new UrlKeluarSah(
            url: $urlBaku,
            skema: $skema,
            host: $host,
            port: $port,
            alamatTersemat: $this->alamatUntukDisemat($daftarAlamat),
            hostBerupaIp: $hostBerupaIp,
        );
    }

    /**
     * Klien HTTP yang hanya boleh dipakai untuk `$sah->url` (atau jalur lain di
     * host yang sama, lewat `baseUrl`).
     *
     * - `CURLOPT_RESOLVE` memaksa curl menyambung ke IP yang sudah diperiksa.
     * - Redirect tidak diikuti: webhook dan API REST tidak semestinya berpindah
     *   alamat, dan mengikuti redirect berarti memeriksa, meresolusi, dan
     *   menyematkan ulang tiap lompatan. Menolak seluruhnya lebih sederhana
     *   dan tidak menyisakan celah; balasan 3xx diperlakukan sebagai gagal.
     * - Middleware menolak permintaan ke host atau port lain, supaya klien ini
     *   tidak diam-diam dipakai untuk URL yang tidak pernah diperiksa.
     *
     * @throws UrlKeluarDitolak
     */
    public function klien(UrlKeluarSah $sah): PendingRequest
    {
        // Tanpa curl, Guzzle jatuh ke stream handler yang mengabaikan CURLOPT_RESOLVE;
        // lebih baik gagal tertutup daripada mengirim tanpa penyematan.
        if (! extension_loaded('curl')) {
            throw new UrlKeluarDitolak('Panggilan keluar membutuhkan ekstensi PHP curl.');
        }

        $opsi = ['allow_redirects' => false, 'protocols' => ['http', 'https']];
        $entri = $sah->entriResolve();
        if ($entri !== null) {
            $opsi['curl'] = [CURLOPT_RESOLVE => [$entri]];
        }

        return Http::withOptions($opsi)->withRequestMiddleware(
            function (RequestInterface $permintaan) use ($sah): RequestInterface {
                $uri = $permintaan->getUri();
                $skema = strtolower($uri->getScheme());
                $port = $uri->getPort() ?? ($skema === 'https' ? 443 : 80);

                if ($skema !== $sah->skema || strtolower($uri->getHost()) !== $sah->host || $port !== $sah->port) {
                    throw new UrlKeluarDitolak('Permintaan keluar menuju alamat yang tidak diperiksa penjaga jaringan.');
                }

                return $permintaan;
            },
        );
    }

    /** Menyingkat `periksa()` lalu `klien()` untuk URL dasar; jalur relatif ditambahkan pemanggil. */
    public function klienDasar(string $urlDasar): PendingRequest
    {
        $sah = $this->periksa($urlDasar);

        return $this->klien($sah)->baseUrl($sah->url);
    }

    public function alamatTerlarang(string $alamat): bool
    {
        $biner = @inet_pton($alamat);
        if ($biner === false) {
            return true;
        }

        if (strlen($biner) === 16) {
            foreach (self::IPV4_TERTANAM as [$awalan, $posisi]) {
                if ($this->dalamCidr($biner, $awalan)) {
                    $ipv4 = inet_ntop(substr($biner, $posisi, 4));

                    return $ipv4 === false || $this->alamatTerlarang($ipv4);
                }
            }
        }

        foreach (strlen($biner) === 4 ? self::BLOK_IPV4 : self::BLOK_IPV6 as $cidr) {
            if ($this->dalamCidr($biner, $cidr)) {
                return true;
            }
        }

        $bendera = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (defined('FILTER_FLAG_GLOBAL_RANGE')) {
            $bendera |= FILTER_FLAG_GLOBAL_RANGE;
        }

        return filter_var($alamat, FILTER_VALIDATE_IP, $bendera) === false;
    }

    /**
     * @return array{0: string, 1: non-empty-list<string>, 2: bool} host untuk URL baku, seluruh alamatnya, dan apakah host berupa IP
     */
    private function resolusi(string $host): array
    {
        if (str_starts_with($host, '[')) {
            $ipv6 = substr($host, 1, -1);
            if (! str_ends_with($host, ']') || filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
                throw new UrlKeluarDitolak('Alamat tidak valid. Tulis alamat lengkap, mis. https://contoh.co.id/webhook.');
            }

            return [$host, [$ipv6], true];
        }

        $host = str_ends_with($host, '.') ? substr($host, 0, -1) : $host;
        if ($host === '' || preg_match('/^[a-z0-9._-]+$/', $host) !== 1) {
            throw new UrlKeluarDitolak('Nama host harus berupa domain lengkap, mis. contoh.co.id.');
        }

        $label = explode('.', $host);
        $labelTerakhir = end($label);

        // Seperti peramban dan curl, host yang label terakhirnya angka dibaca sebagai IPv4,
        // termasuk bentuk desimal tunggal (2130706433), heksa (0x7f000001), dan oktal (0177.0.0.1).
        if (preg_match('/^(0x[0-9a-f]*|[0-9]+)$/', $labelTerakhir) === 1) {
            $ipv4 = $this->ipv4DariBentukAngka($label);
            if ($ipv4 === null) {
                throw new UrlKeluarDitolak('Alamat IP tidak valid.');
            }
            if ($this->alamatTerlarang($ipv4)) {
                throw new UrlKeluarDitolak(UrlKeluarDitolak::PESAN_JARINGAN_INTERNAL);
            }
            if ($ipv4 !== $host) {
                throw new UrlKeluarDitolak('Alamat IP harus ditulis dalam bentuk baku: empat bilangan desimal bertitik.');
            }

            return [$host, [$ipv4], true];
        }

        foreach (self::AKHIRAN_LOKAL as $akhiran) {
            if ($host === $akhiran || str_ends_with($host, '.'.$akhiran)) {
                throw new UrlKeluarDitolak(UrlKeluarDitolak::PESAN_JARINGAN_INTERNAL);
            }
        }

        // Nama satu label ("redis", "metadata") diselesaikan lewat domain pencarian
        // resolver lokal, jadi hampir pasti menunjuk jaringan internal.
        if (count($label) < 2 || in_array('', $label, true)) {
            throw new UrlKeluarDitolak('Nama host harus berupa domain lengkap, mis. contoh.co.id.');
        }

        $daftarAlamat = $this->dns->alamat($host);
        if ($daftarAlamat === []) {
            throw new UrlKeluarDitolak('Nama host tidak dapat ditemukan. Periksa kembali alamatnya.', sementara: true);
        }

        return [$host, $daftarAlamat, false];
    }

    /**
     * Tafsiran inet_aton: 1-4 bagian desimal, oktal (awalan 0), atau heksa (awalan 0x);
     * bagian terakhir mengisi seluruh byte yang tersisa.
     *
     * @param  list<string>  $bagian
     */
    private function ipv4DariBentukAngka(array $bagian): ?string
    {
        if (count($bagian) > 4) {
            return null;
        }

        $angka = [];
        foreach ($bagian as $satu) {
            $nilai = match (true) {
                preg_match('/^0x[0-9a-f]*$/', $satu) === 1 => $satu === '0x' ? 0 : hexdec(substr($satu, 2)),
                preg_match('/^0[0-7]*$/', $satu) === 1 => octdec($satu),
                preg_match('/^[1-9][0-9]*$/', $satu) === 1 => strlen($satu) > 10 ? null : (int) $satu,
                default => null,
            };
            if (! is_int($nilai)) {
                return null;
            }
            $angka[] = $nilai;
        }

        $terakhir = array_pop($angka);
        $sisaByte = 4 - count($angka);
        if ($terakhir >= 256 ** $sisaByte) {
            return null;
        }

        $nilaiPenuh = 0;
        foreach ($angka as $indeks => $oktet) {
            if ($oktet > 255) {
                return null;
            }
            $nilaiPenuh += $oktet * 256 ** (3 - $indeks);
        }

        $ipv4 = long2ip($nilaiPenuh + $terakhir);

        return $ipv4 === false ? null : $ipv4;
    }

    /** Bagian otoritas mentah: sesudah "skema://" sampai "/", "?", atau "#" pertama. */
    private function otoritas(string $url, string $skema): string
    {
        $awalan = $skema.'://';
        if (strncasecmp($url, $awalan, strlen($awalan)) !== 0) {
            throw new UrlKeluarDitolak('Alamat tidak valid. Tulis alamat lengkap, mis. https://contoh.co.id/webhook.');
        }

        $sisa = substr($url, strlen($awalan));
        $panjang = strcspn($sisa, '/?#');

        return strtolower(substr($sisa, 0, $panjang));
    }

    /** @return list<int> */
    private function portDiizinkan(): array
    {
        $tambahan = config('amanpoll.http_keluar.port_tambahan', []);

        return [80, 443, ...array_map(intval(...), is_array($tambahan) ? array_values($tambahan) : [])];
    }

    /**
     * Shared hosting sering tanpa rute IPv6, jadi IPv4 didahulukan.
     *
     * @param  non-empty-list<string>  $daftarAlamat
     */
    private function alamatUntukDisemat(array $daftarAlamat): string
    {
        foreach ($daftarAlamat as $alamat) {
            if (filter_var($alamat, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                return $alamat;
            }
        }

        return $daftarAlamat[0];
    }

    private function dalamCidr(string $biner, string $cidr): bool
    {
        [$jaringan, $panjang] = explode('/', $cidr);
        $binerJaringan = inet_pton($jaringan);
        if ($binerJaringan === false || strlen($binerJaringan) !== strlen($biner)) {
            return false;
        }

        $panjang = (int) $panjang;
        $byteUtuh = intdiv($panjang, 8);
        if (substr($biner, 0, $byteUtuh) !== substr($binerJaringan, 0, $byteUtuh)) {
            return false;
        }

        $sisaBit = $panjang % 8;
        if ($sisaBit === 0) {
            return true;
        }

        $topeng = (0xFF << (8 - $sisaBit)) & 0xFF;

        return (ord($biner[$byteUtuh]) & $topeng) === (ord($binerJaringan[$byteUtuh]) & $topeng);
    }
}
