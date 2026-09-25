<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Services;

use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PeristiwaWebhookWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\PersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanMasukWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanWhatsApp;
use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * WhatsApp Cloud API resmi dari Meta (graph.facebook.com).
 *
 * Naskah kami memakai variabel bernama (`{{Nama}}`), sedangkan Meta hanya menerima nama
 * huruf kecil atau nomor urut. Karena itu naskah diajukan dengan nomor urut `{{1}}`, `{{2}}`
 * menurut kemunculannya, dan saat mengirim nilai tiap variabel dibaca kembali dari teks yang
 * sudah dirender dengan mencocokkannya ke naskah template.
 */
final class PenyediaWhatsAppMetaCloud extends PenyediaWhatsAppHttp
{
    private const VERSI_BAWAAN = 'v21.0';

    private const BAHASA_NOTIFIKASI_BAWAAN = 'id';

    /** Badan template dibatasi 1024 karakter oleh Meta; sisanya untuk naskah template itu sendiri. */
    private const BATAS_JUDUL_NOTIFIKASI = 100;

    private const BATAS_ISI_NOTIFIKASI = 700;

    private const POLA_VARIABEL = '/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/';

    /** Kode galat Meta untuk penerima yang memilih berhenti menerima pesan pemasaran. */
    private const GALAT_PENERIMA_BERHENTI = 131050;

    public function kode(): string
    {
        return 'MetaCloud';
    }

    public function nama(): string
    {
        return 'WhatsApp Cloud API (Meta)';
    }

    public function keterangan(): string
    {
        return 'API resmi Meta: template wajib disetujui Meta dan pesan dikirim dari nomor WhatsApp Business terverifikasi.';
    }

    public function resmi(): bool
    {
        return true;
    }

    /** Meta tidak punya sandbox terpisah; uji coba memakai nomor uji dengan alamat API yang sama. */
    public function mendukungModeUji(): bool
    {
        return false;
    }

    /** @return list<IsianKredensial> */
    public function isian(): array
    {
        return [
            new IsianKredensial('PhoneNumberId', 'Phone number ID', petunjuk: 'WhatsApp Manager > API Setup, pengenal nomor pengirim.'),
            new IsianKredensial('WabaId', 'WhatsApp Business Account ID', petunjuk: 'Dipakai untuk mengajukan dan memeriksa template.', hanyaPlatform: true),
            new IsianKredensial('AccessToken', 'Access token', rahasia: true, petunjuk: 'Token permanen System User dengan izin whatsapp_business_messaging dan whatsapp_business_management.'),
            new IsianKredensial('AppSecret', 'App secret', rahasia: true, petunjuk: 'Pengaturan aplikasi Meta > Dasar; memverifikasi tanda tangan webhook.', hanyaPlatform: true),
            new IsianKredensial('VerifyToken', 'Verify token webhook', rahasia: true, petunjuk: 'Teks acak buatan Anda; isikan juga di pengaturan webhook aplikasi Meta.', hanyaPlatform: true),
            new IsianKredensial('VersiGraph', 'Versi Graph API', wajib: false, petunjuk: 'Mis. v21.0.', bawaan: self::VERSI_BAWAAN),
            new IsianKredensial(
                'TemplateNotifikasi',
                'Template notifikasi staf',
                wajib: false,
                petunjuk: 'Nama template kategori Utility yang sudah disetujui Meta, berbadan dua parameter: {{1}} judul dan {{2}} isi. Tanpa ini notifikasi WhatsApp ke staf tidak dapat dikirim.',
                // Nomor organisasi hanya dipakai untuk notifikasi, jadi tanpa template ia tidak berguna.
                wajibOrganisasi: true,
            ),
            new IsianKredensial('BahasaTemplateNotifikasi', 'Bahasa template notifikasi', wajib: false, petunjuk: 'Kode bahasa template di Meta, mis. id atau en_US.', bawaan: self::BAHASA_NOTIFIKASI_BAWAAN),
        ];
    }

    public function kirim(PesanWhatsApp $pesan): string
    {
        $kredensial = $this->kredensial();
        $template = [
            'name' => $this->namaTemplate($pesan->kodeTemplate),
            'language' => ['code' => $pesan->bahasa],
        ];

        $parameter = $this->parameterDari($pesan->naskahTemplate ?? '', $pesan->isiTeks);

        if ($parameter !== []) {
            $template['components'] = [[
                'type' => 'body',
                'parameters' => array_map(fn (string $nilai): array => ['type' => 'text', 'text' => $nilai], $parameter),
            ]];
        }

        return $this->kirimPesan($kredensial, [
            'to' => $this->nomor($pesan->kepada),
            'type' => 'template',
            'template' => $template,
        ]);
    }

    public function balas(string $kepada, string $teks): string
    {
        return $this->kirimPesan($this->kredensial(), [
            'to' => $this->nomor($kepada),
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $teks],
        ]);
    }

    /** Pesan yang dimulai bisnis wajib memakai template yang disetujui Meta, jadi teks bebas tidak dicoba. */
    public function kirimNotifikasi(string $nomor, string $judul, string $isi): string
    {
        return $this->kirimNotifikasiDengan($this->kredensial(), $nomor, $judul, $isi);
    }

    public function kirimNotifikasiDengan(KredensialPenyedia $kredensial, string $nomor, string $judul, string $isi): string
    {
        $template = $kredensial->ambilAtau('TemplateNotifikasi');

        if ($template === '') {
            throw new AturanBisnisDilanggar(
                "Template notifikasi WhatsApp Cloud API belum diatur di {$kredensial->tempat} (isian \"Template notifikasi staf\"). Meta hanya mengizinkan pesan yang dimulai bisnis lewat template yang disetujui.",
            );
        }

        return $this->kirimPesan($kredensial, [
            'to' => $this->nomor($nomor),
            'type' => 'template',
            'template' => [
                'name' => $this->namaTemplate($template),
                'language' => ['code' => $kredensial->ambilAtau('BahasaTemplateNotifikasi', self::BAHASA_NOTIFIKASI_BAWAAN)],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $this->parameterNotifikasi($judul, self::BATAS_JUDUL_NOTIFIKASI)],
                        ['type' => 'text', 'text' => $this->parameterNotifikasi($isi, self::BATAS_ISI_NOTIFIKASI)],
                    ],
                ]],
            ],
        ]);
    }

    public function ajukanTemplate(
        string $kode,
        string $bahasa,
        string $kategori,
        string $isiTeks,
    ): PersetujuanTemplateWa {
        $kredensial = $this->kredensial();
        $variabel = [];
        $urutan = 0;

        $naskah = (string) preg_replace_callback(self::POLA_VARIABEL, function (array $cocok) use (&$variabel, &$urutan): string {
            $variabel[] = $cocok[1];

            return '{{'.++$urutan.'}}';
        }, $isiTeks);

        $badan = ['type' => 'BODY', 'text' => $naskah];

        if ($variabel !== []) {
            // Meta menolak template bervariabel tanpa contoh; nama variabelnya menjadi contoh yang jujur.
            $badan['example'] = ['body_text' => [$variabel]];
        }

        $jawaban = $this->panggil($kredensial, 'pengajuan template', fn (): Response => $this->http($kredensial)
            ->post($kredensial->ambil('WabaId').'/message_templates', [
                'name' => $this->namaTemplate($kode),
                'language' => $bahasa,
                'category' => $this->kategoriMeta($kategori),
                'components' => [$badan],
            ]));

        return new PersetujuanTemplateWa(
            $this->statusTemplate($this->teksDari($jawaban, 'status')),
            $this->teksDari($jawaban, 'id') ?: null,
        );
    }

    public function periksaTemplate(string $kode): PersetujuanTemplateWa
    {
        $kredensial = $this->kredensial();
        $nama = $this->namaTemplate($kode);

        $jawaban = $this->panggil($kredensial, 'pemeriksaan template', fn (): Response => $this->http($kredensial)
            ->get($kredensial->ambil('WabaId').'/message_templates', [
                'name' => $nama,
                'fields' => 'id,name,status,language,rejected_reason',
            ]));

        $daftar = $jawaban->json('data');

        foreach (is_array($daftar) ? $daftar : [] as $satu) {
            if (! is_array($satu) || $this->teksDalam($satu, 'name') !== $nama) {
                continue;
            }

            $alasan = $this->teksDalam($satu, 'rejected_reason');

            return new PersetujuanTemplateWa(
                $this->statusTemplate($this->teksDalam($satu, 'status')),
                $this->teksDalam($satu, 'id') ?: null,
                in_array($alasan, ['', 'NONE'], true) ? null : $alasan,
            );
        }

        return new PersetujuanTemplateWa(
            StatusPersetujuanTemplateWa::Ditolak,
            alasan: "Template {$nama} tidak ditemukan di akun WhatsApp Business.",
        );
    }

    /** @param array<string, mixed> $kueri */
    public function verifikasiLangganan(array $kueri): ?string
    {
        $harapan = $this->kredensialAtauKosong()?->ambilAtau('VerifyToken') ?? '';

        // PHP mengubah titik pada nama parameter kueri menjadi garis bawah (`hub.mode` → `hub_mode`).
        $mode = $kueri['hub_mode'] ?? $kueri['hub.mode'] ?? null;
        $token = $kueri['hub_verify_token'] ?? $kueri['hub.verify_token'] ?? null;
        $tantangan = $kueri['hub_challenge'] ?? $kueri['hub.challenge'] ?? null;

        if ($harapan === '' || $mode !== 'subscribe' || ! is_string($token) || ! is_string($tantangan)) {
            return null;
        }

        return hash_equals($harapan, $token) ? $tantangan : null;
    }

    /**
     * @param  array<string, string>  $header
     * @param  array<string, mixed>  $kueri
     */
    public function webhookSah(string $badanMentah, array $header, array $kueri): bool
    {
        $rahasia = $this->kredensialAtauKosong()?->ambilAtau('AppSecret') ?? '';
        $tanda = $header['x-hub-signature-256'] ?? '';

        if ($rahasia === '' || ! str_starts_with($tanda, 'sha256=')) {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $badanMentah, $rahasia), $tanda);
    }

    /** @param array<mixed> $muatan */
    public function terjemahkanWebhook(array $muatan): PeristiwaWebhookWhatsApp
    {
        $status = [];
        $pesanMasuk = [];

        foreach ($this->daftar($muatan, 'entry') as $entri) {
            foreach ($this->daftar($entri, 'changes') as $perubahan) {
                foreach ($this->daftar($perubahan, 'value.statuses') as $laporan) {
                    $satu = $this->laporanStatus($laporan);

                    if ($satu !== null) {
                        $status[] = $satu;
                    }
                }

                foreach ($this->daftar($perubahan, 'value.messages') as $pesan) {
                    $teks = $this->teksPesan($pesan);
                    $dari = $this->teksDalam($pesan, 'from');

                    if ($teks !== '' && $dari !== '') {
                        $pesanMasuk[] = new PesanMasukWhatsApp($dari, $teks, $this->teksDalam($pesan, 'id') ?: null);
                    }
                }
            }
        }

        return new PeristiwaWebhookWhatsApp($status, $pesanMasuk);
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $jawaban = $this->panggil($kredensial, 'pemeriksaan nomor', fn (): Response => $this->http($kredensial)
            ->get($kredensial->ambil('PhoneNumberId'), ['fields' => 'display_phone_number,verified_name']));

        return new HasilUjiKoneksi(
            true,
            "Terhubung ke nomor {$this->teksDari($jawaban, 'display_phone_number')} ({$this->teksDari($jawaban, 'verified_name')}).",
        );
    }

    protected function pesanGalat(Response $jawaban): string
    {
        return $this->teksDari($jawaban, 'error.error_user_msg') ?: $this->teksDari($jawaban, 'error.message');
    }

    /** @param array<string, mixed> $isi */
    private function kirimPesan(KredensialPenyedia $kredensial, array $isi): string
    {
        $jawaban = $this->panggil($kredensial, 'pengiriman pesan', fn (): Response => $this->http($kredensial)
            ->post($kredensial->ambil('PhoneNumberId').'/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                ...$isi,
            ]));

        $id = $this->teksDari($jawaban, 'messages.0.id');

        return $id !== '' ? $id : throw $this->galat($kredensial, 'pengiriman pesan', $jawaban, 'jawaban tidak memuat id pesan.');
    }

    /**
     * Nilai tiap kemunculan variabel, dibaca dari teks yang sudah dirender.
     *
     * Kemunculan kedua variabel yang sama dicocokkan dengan rujukan balik, sehingga
     * pencocokan hanya berhasil bila nilainya memang sama.
     *
     * @return list<string>
     */
    private function parameterDari(string $naskah, string $terender): array
    {
        $bagian = preg_split(self::POLA_VARIABEL, $naskah, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($bagian === false || count($bagian) === 1) {
            return [];
        }

        $pola = '';
        $grup = [];
        $urutanGrup = [];

        foreach ($bagian as $indeks => $potongan) {
            if ($indeks % 2 === 0) {
                $pola .= preg_quote($potongan, '/');

                continue;
            }

            if (! isset($grup[$potongan])) {
                $grup[$potongan] = count($grup) + 1;
                $pola .= '(.*?)';
            } else {
                $pola .= '\\g{'.$grup[$potongan].'}';
            }

            $urutanGrup[] = $grup[$potongan];
        }

        if (preg_match('/^'.$pola.'$/su', $terender, $cocok) !== 1) {
            throw new AturanBisnisDilanggar(
                'Isi pesan tidak lagi cocok dengan naskah template yang disetujui; jadwalkan ulang pesannya.',
            );
        }

        // Meta menolak parameter kosong, padahal variabel tanpa nilai memang dirender kosong.
        return array_map(
            fn (int $nomor): string => trim($cocok[$nomor] ?? '') === '' ? '-' : $cocok[$nomor],
            $urutanGrup,
        );
    }

    /** Meta menolak parameter kosong, berbaris baru, atau berspasi beruntun, jadi teksnya dirapatkan lalu dipotong. */
    private function parameterNotifikasi(string $teks, int $batas): string
    {
        $rapat = trim((string) preg_replace('/\s+/u', ' ', $teks));

        if ($rapat === '') {
            return '-';
        }

        return mb_strlen($rapat) > $batas ? rtrim(mb_substr($rapat, 0, $batas - 1)).'…' : $rapat;
    }

    /** Meta hanya menerima huruf kecil, angka, dan garis bawah untuk nama template. */
    private function namaTemplate(string $kode): string
    {
        $nama = trim((string) preg_replace('/[^a-z0-9_]+/', '_', mb_strtolower($kode)), '_');

        return $nama !== '' ? $nama : throw new AturanBisnisDilanggar("Kode template {$kode} tidak dapat dijadikan nama template Meta.");
    }

    private function kategoriMeta(string $kategori): string
    {
        $besar = mb_strtoupper(trim($kategori));

        return in_array($besar, ['MARKETING', 'UTILITY', 'AUTHENTICATION'], true) ? $besar : 'MARKETING';
    }

    private function statusTemplate(string $status): StatusPersetujuanTemplateWa
    {
        return match (mb_strtoupper($status)) {
            'APPROVED' => StatusPersetujuanTemplateWa::Disetujui,
            'REJECTED', 'DISABLED', 'DELETED', 'PENDING_DELETION' => StatusPersetujuanTemplateWa::Ditolak,
            'PAUSED', 'LIMIT_EXCEEDED' => StatusPersetujuanTemplateWa::Ditangguhkan,
            default => StatusPersetujuanTemplateWa::Diajukan,
        };
    }

    /** @param array<mixed> $laporan */
    private function laporanStatus(array $laporan): ?StatusKirimanWhatsApp
    {
        $id = $this->teksDalam($laporan, 'id');
        $kodeGalat = data_get($laporan, 'errors.0.code');

        $status = match ($this->teksDalam($laporan, 'status')) {
            'sent' => StatusPengirimanWhatsApp::Dikirim,
            'delivered' => StatusPengirimanWhatsApp::Terkirim,
            'read' => StatusPengirimanWhatsApp::Dibaca,
            'failed' => is_numeric($kodeGalat) && (int) $kodeGalat === self::GALAT_PENERIMA_BERHENTI
                ? StatusPengirimanWhatsApp::Unsubscribe
                : StatusPengirimanWhatsApp::Gagal,
            default => null,
        };

        if ($id === '' || $status === null) {
            return null;
        }

        $keterangan = $this->teksDalam($laporan, 'errors.0.title') ?: $this->teksDalam($laporan, 'errors.0.message');

        return new StatusKirimanWhatsApp($id, $status, $keterangan ?: null);
    }

    /** @param array<mixed> $pesan */
    private function teksPesan(array $pesan): string
    {
        return match ($this->teksDalam($pesan, 'type')) {
            'text' => $this->teksDalam($pesan, 'text.body'),
            'button' => $this->teksDalam($pesan, 'button.text'),
            'interactive' => $this->teksDalam($pesan, 'interactive.button_reply.title')
                ?: $this->teksDalam($pesan, 'interactive.list_reply.title'),
            default => '',
        };
    }

    /**
     * @param  array<mixed>  $data
     * @return list<array<mixed>>
     */
    private function daftar(array $data, string $kunci): array
    {
        $nilai = data_get($data, $kunci);

        return is_array($nilai) ? array_values(array_filter($nilai, is_array(...))) : [];
    }

    private function http(KredensialPenyedia $kredensial): PendingRequest
    {
        return Http::baseUrl('https://graph.facebook.com/'.$this->versi($kredensial))
            ->withToken($kredensial->ambil('AccessToken'))
            ->acceptJson()
            ->timeout(self::BATAS_WAKTU_DETIK);
    }

    /** Versi masuk ke jalur URL, jadi hanya bentuk `v<angka>.<angka>` yang dipakai. */
    private function versi(KredensialPenyedia $kredensial): string
    {
        $versi = $kredensial->ambilAtau('VersiGraph', self::VERSI_BAWAAN);

        return preg_match('/^v\d{1,3}\.\d{1,2}$/', $versi) === 1 ? $versi : self::VERSI_BAWAAN;
    }
}
