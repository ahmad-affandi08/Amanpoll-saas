<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Services;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Shared\Infrastructure\Kompresi\HasilPemampatan;
use App\Shared\Infrastructure\Kompresi\MetodeKompresi;
use App\Shared\Infrastructure\Kompresi\PemampatBerkas;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Satu jalur simpan, baca, dan hapus untuk `Berkas` (PRD 11 dan 11.1).
 *
 * - Simpan: isi dipadatkan lewat PemampatBerkas, `HashSha256` dihitung atas isi
 *   ASLI, lalu salinan fisik dipakai bersama bila organisasi yang sama sudah
 *   punya berkas aktif dengan hash, metode kompresi, dan disk yang sama.
 * - Hapus: salinan fisik (dan thumbnail) baru dihapus bila tidak ada lagi
 *   `Berkas` aktif yang merujuknya.
 * - Baca: gzip dibuka secara streaming; pengguna selalu menerima isi aslinya.
 *
 * Simpan dan hapus untuk satu hash dijalankan di bawah kunci cache yang sama,
 * sehingga penghapusan tidak dapat menyelinap di antara "salinan ditemukan" dan
 * "baris baru tercatat".
 */
final class PenyimpanBerkas
{
    private const DETIK_KUNCI = 60;

    private const DETIK_TUNGGU_KUNCI = 20;

    /** Header cache thumbnail: pribadi (berkas terotorisasi), boleh disimpan peramban sehari. */
    public const CACHE_THUMBNAIL = 'private, max-age=86400';

    public function __construct(
        private readonly PemampatBerkas $pemampat,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    /**
     * Unggahan pengguna. Ekstensi nama penyimpanan ditebak dari MIME hasil
     * deteksi server; nama dari klien hanya disimpan sebagai `NamaAsli`.
     *
     * @param  array<string, mixed>|null  $dataTambahan
     */
    public function simpanUnggahan(UploadedFile $berkas, ?string $pengunggahId, ?array $dataTambahan = null): Berkas
    {
        $lokasi = $berkas->getRealPath();
        if ($lokasi === false) {
            throw new RuntimeException('Berkas unggahan tidak dapat dibaca.');
        }

        return $this->simpan(
            lokasiSumber: $lokasi,
            jenisMime: $berkas->getMimeType() ?? 'application/octet-stream',
            namaAsli: $berkas->getClientOriginalName(),
            pengunggahId: $pengunggahId,
            ekstensi: $berkas->extension() ?: null,
            dataTambahan: $dataTambahan,
        );
    }

    /**
     * Berkas lokal apa pun (hasil sistem, berkas sementara). Berkas sumber tidak
     * dihapus; itu urusan pemanggil. Wajib dalam konteks organisasi.
     *
     * @param  string  $jenisMime  MIME sebenarnya; untuk berkas buatan sistem, MIME format yang ditulis.
     * @param  ?string  $ekstensi  Ekstensi dasar; ditebak dari MIME bila null.
     * @param  array<string, mixed>|null  $dataTambahan
     * @param  ?string  $direktori  Folder di disk; bawaan `berkas/{OrganisasiId}`.
     * @param  ?string  $disk  Bawaan `amanpoll.disk_berkas`.
     */
    public function simpan(
        string $lokasiSumber,
        string $jenisMime,
        string $namaAsli,
        ?string $pengunggahId = null,
        ?string $ekstensi = null,
        ?array $dataTambahan = null,
        ?string $direktori = null,
        ?string $disk = null,
    ): Berkas {
        $organisasiId = $this->konteks->wajibId();
        $disk ??= (string) config('amanpoll.disk_berkas', 'local');
        $direktori = trim($direktori ?? 'berkas/'.$organisasiId, '/');

        $hash = hash_file('sha256', $lokasiSumber);
        if ($hash === false) {
            throw new RuntimeException('Berkas sumber tidak dapat dibaca.');
        }

        $hasil = $this->pemampat->pampatkan($lokasiSumber, $jenisMime, $namaAsli, $ekstensi);

        try {
            return $this->dalamKunci($organisasiId, $hash, fn (): Berkas => $this->tulisDanCatat(
                $hasil,
                $hash,
                $disk,
                $direktori,
                [
                    'NamaAsli' => $namaAsli,
                    'DataTambahan' => $dataTambahan,
                    'DiunggahOleh' => $pengunggahId,
                ],
            ));
        } finally {
            $hasil->bersihkan();
        }
    }

    /**
     * Menghapus (lunak) baris `Berkas` dan salinan fisiknya bila tidak ada baris
     * aktif lain yang merujuknya. Lampiran yang menunjuk berkas ini urusan pemanggil.
     */
    public function hapus(Berkas $berkas): void
    {
        $this->dalamKunci((string) $berkas->OrganisasiId, $berkas->HashSha256 ?? 'lokasi:'.sha1($berkas->LokasiPenyimpanan), function () use ($berkas): void {
            $berkas->delete();
            $this->hapusFisikBilaYatim($berkas);
        });
    }

    /**
     * Menghapus salinan fisik dan thumbnail milik baris ini bila tidak ada
     * `Berkas` aktif yang merujuknya. Aman dipanggil untuk baris yang sudah
     * dihapus: pemeriksaan melewati ScopeOrganisasi (tetap disaring ke
     * organisasi baris itu), jadi perintah artisan tanpa konteks organisasi
     * tidak keliru menganggap salinannya yatim.
     */
    public function hapusFisikBilaYatim(Berkas $berkas): void
    {
        $organisasiId = (string) $berkas->OrganisasiId;

        $this->hapusBilaYatim($organisasiId, $berkas->MediaPenyimpanan, 'LokasiPenyimpanan', $berkas->LokasiPenyimpanan);
        if ($berkas->LokasiThumbnail !== null) {
            $this->hapusBilaYatim($organisasiId, $berkas->MediaPenyimpanan, 'LokasiThumbnail', $berkas->LokasiThumbnail);
        }
    }

    /**
     * Aliran isi ASLI berkas (gzip sudah dibuka), dibaca per potongan.
     * Pemanggil yang menutupnya.
     *
     * @return resource
     */
    public function bukaAliran(Berkas $berkas)
    {
        $aliran = Storage::disk($berkas->MediaPenyimpanan)->readStream($berkas->LokasiPenyimpanan);
        if (! is_resource($aliran)) {
            throw new RuntimeException('Isi berkas tidak ditemukan di penyimpanan.');
        }

        if ($berkas->MetodeKompresi === MetodeKompresi::Gzip) {
            // window 31 = zlib dengan kepala gzip.
            stream_filter_append($aliran, 'zlib.inflate', STREAM_FILTER_READ, ['window' => 31]);
        }

        return $aliran;
    }

    /**
     * Respons unduhan: nama, MIME, dan ukuran yang diterima pengguna. Gzip
     * dibuka sambil dialirkan, tanpa memuat seluruh berkas ke memori.
     */
    public function responsUnduh(Berkas $berkas): StreamedResponse
    {
        $media = Storage::disk($berkas->MediaPenyimpanan);
        abort_unless($media->exists($berkas->LokasiPenyimpanan), 404);

        $header = ['Content-Type' => $berkas->JenisMime ?: 'application/octet-stream'];

        if ($berkas->MetodeKompresi !== MetodeKompresi::Gzip) {
            return $media->download($berkas->LokasiPenyimpanan, $berkas->namaUnduhan(), $header);
        }

        if ($berkas->UkuranAsliByte !== null) {
            $header['Content-Length'] = (string) $berkas->UkuranAsliByte;
        }

        return response()->streamDownload(function () use ($berkas): void {
            $aliran = $this->bukaAliran($berkas);
            try {
                fpassthru($aliran);
            } finally {
                fclose($aliran);
            }
        }, $berkas->namaUnduhan(), $header);
    }

    /**
     * Thumbnail WebP untuk daftar dan galeri; gambar tanpa thumbnail (sudah
     * kecil, atau berkas lama) dikirim gambar aslinya. Bukan gambar: 404.
     */
    public function responsThumbnail(Berkas $berkas): StreamedResponse
    {
        $media = Storage::disk($berkas->MediaPenyimpanan);
        $header = ['Cache-Control' => self::CACHE_THUMBNAIL];

        if ($berkas->LokasiThumbnail !== null && $media->exists($berkas->LokasiThumbnail)) {
            $nama = pathinfo($berkas->namaUnduhan(), PATHINFO_FILENAME).'-thumbnail.webp';

            return $media->response($berkas->LokasiThumbnail, $nama, $header + ['Content-Type' => 'image/webp']);
        }

        abort_unless(
            $berkas->MetodeKompresi !== MetodeKompresi::Gzip
                && str_starts_with((string) $berkas->JenisMime, 'image/')
                && $media->exists($berkas->LokasiPenyimpanan),
            404,
        );

        return $media->response($berkas->LokasiPenyimpanan, $berkas->namaUnduhan(), $header + ['Content-Type' => (string) $berkas->JenisMime]);
    }

    /**
     * Memadatkan ulang satu salinan fisik lama (perintah `berkas:pampatkan`, PRD 11.1).
     *
     * Semua baris organisasi itu yang merujuk `(MediaPenyimpanan, LokasiPenyimpanan)`
     * yang sama diperbarui bersama dalam satu transaksi, di bawah kunci hash yang
     * sama dengan simpan dan hapus. Salinan lama baru dihapus sesudah salinan baru
     * tertulis, terverifikasi (dibaca balik dan di-hash), dan tercatat; kegagalan
     * apa pun meninggalkan salinan lama dan barisnya utuh. Gambar yang belum punya
     * thumbnail dibuatkan, walau isinya sendiri tidak dapat diperkecil.
     *
     * Tidak butuh konteks organisasi: baris dibaca melewati ScopeOrganisasi dan
     * disaring ke organisasi `$wakil`.
     *
     * @return array{Hasil: 'dipadatkan'|'thumbnail'|'tetap'|'gagal', JumlahBaris: int, UkuranSebelum: int, UkuranSesudah: int, Pesan: ?string}
     */
    public function pampatkanUlang(Berkas $wakil, bool $pratinjau = false): array
    {
        $kunci = $wakil->HashSha256 ?? 'lokasi:'.sha1($wakil->LokasiPenyimpanan);

        return $this->dalamKunci((string) $wakil->OrganisasiId, $kunci, fn (): array => $this->pampatkanUlangDalamKunci($wakil, $pratinjau));
    }

    /**
     * @return array{Hasil: 'dipadatkan'|'thumbnail'|'tetap'|'gagal', JumlahBaris: int, UkuranSebelum: int, UkuranSesudah: int, Pesan: ?string}
     */
    private function pampatkanUlangDalamKunci(Berkas $wakil, bool $pratinjau): array
    {
        $organisasiId = (string) $wakil->OrganisasiId;
        $disk = $wakil->MediaPenyimpanan;
        $lokasiLama = $wakil->LokasiPenyimpanan;
        $media = Storage::disk($disk);
        $hasil = ['Hasil' => 'tetap', 'JumlahBaris' => 0, 'UkuranSebelum' => 0, 'UkuranSesudah' => 0, 'Pesan' => null];

        $saudara = fn (): Builder => Berkas::query()
            ->withoutGlobalScope(ScopeOrganisasi::class)
            ->where('OrganisasiId', $organisasiId)
            ->where('MediaPenyimpanan', $disk)
            ->where('LokasiPenyimpanan', $lokasiLama);

        // Dibaca ulang di bawah kunci: proses lain mungkin baru saja memadatkannya.
        $segar = $saudara()->whereKey($wakil->Id)->first();
        if ($segar === null || $segar->MetodeKompresi !== MetodeKompresi::Tidak) {
            return $hasil;
        }

        $hasil['JumlahBaris'] = $saudara()->count();

        if (! $media->exists($lokasiLama)) {
            return ['Pesan' => 'Salinan fisik tidak ditemukan.', 'Hasil' => 'gagal'] + $hasil;
        }

        $hasil['UkuranSebelum'] = $hasil['UkuranSesudah'] = (int) $media->size($lokasiLama);
        $sementara = null;
        $pemampatan = null;
        $ditulis = [];

        try {
            $sementara = $this->salinKeSementara($media, $lokasiLama);
            $hash = hash_file('sha256', $sementara);
            if ($hash === false || ($segar->HashSha256 !== null && ! hash_equals($segar->HashSha256, $hash))) {
                return ['Pesan' => 'Isi tidak cocok dengan hash tercatat; dilewati.', 'Hasil' => 'gagal'] + $hasil;
            }

            $pemampatan = $this->pemampat->pampatkan(
                $sementara,
                (string) $segar->JenisMime,
                $segar->NamaAsli,
                pathinfo($lokasiLama, PATHINFO_EXTENSION) ?: null,
            );

            $lebihKecil = $pemampatan->metode !== MetodeKompresi::Tidak || $pemampatan->ukuranTersimpan < $hasil['UkuranSebelum'];
            $perluThumbnail = $pemampatan->lokasiThumbnail !== null && $segar->LokasiThumbnail === null;

            if (! $lebihKecil && ! $perluThumbnail) {
                return $hasil;
            }

            $hasil['Hasil'] = $lebihKecil ? 'dipadatkan' : 'thumbnail';
            if ($lebihKecil) {
                $hasil['UkuranSesudah'] = $pemampatan->ukuranTersimpan;
            }

            if ($pratinjau) {
                return $hasil;
            }

            $direktori = dirname($lokasiLama);
            $ulid = strtolower((string) Str::ulid());
            $perubahan = [];

            if ($lebihKecil) {
                $kembar = Berkas::query()
                    ->withoutGlobalScope(ScopeOrganisasi::class)
                    ->where('OrganisasiId', $organisasiId)
                    ->where('HashSha256', $hash)
                    ->where('MetodeKompresi', $pemampatan->metode->value)
                    ->where('MediaPenyimpanan', $disk)
                    ->where('LokasiPenyimpanan', '!=', $lokasiLama)
                    ->oldest('DibuatPada')
                    ->first();

                if ($kembar !== null && $media->exists($kembar->LokasiPenyimpanan)) {
                    $lokasiBaru = $kembar->LokasiPenyimpanan;
                    $hasil['UkuranSesudah'] = $kembar->UkuranTersimpanByte ?? (int) $media->size($lokasiBaru);
                    if ($kembar->LokasiThumbnail !== null && $media->exists($kembar->LokasiThumbnail)) {
                        $perubahan['LokasiThumbnail'] = $kembar->LokasiThumbnail;
                    }
                } else {
                    $lokasiBaru = $direktori.'/'.$ulid.'.'.$pemampatan->ekstensi;
                    $this->tulisAliran($media, $lokasiBaru, $pemampatan->bukaIsi());
                    $ditulis['LokasiPenyimpanan'] = $lokasiBaru;

                    $harapan = $pemampatan->metode === MetodeKompresi::Gzip ? $hash : hash_file('sha256', $pemampatan->lokasiIsi);
                    if ($this->hashTersimpan($media, $lokasiBaru, $pemampatan->metode) !== $harapan) {
                        throw new RuntimeException('Salinan baru tidak lolos verifikasi baca-balik.');
                    }
                }

                $perubahan += [
                    'LokasiPenyimpanan' => $lokasiBaru,
                    'NamaPenyimpanan' => basename($lokasiBaru),
                    'JenisMime' => $pemampatan->jenisMime,
                    'UkuranByte' => $pemampatan->metode === MetodeKompresi::Gzip ? $pemampatan->ukuranAsli : $hasil['UkuranSesudah'],
                    'MetodeKompresi' => $pemampatan->metode->value,
                    'UkuranAsliByte' => $pemampatan->ukuranAsli,
                    'UkuranTersimpanByte' => $hasil['UkuranSesudah'],
                ];
            }

            if (! isset($perubahan['LokasiThumbnail']) && ($aliranThumbnail = $pemampatan->bukaThumbnail()) !== null) {
                $lokasiThumbnail = $direktori.'/thumbnail/'.$ulid.'.webp';
                $this->tulisAliran($media, $lokasiThumbnail, $aliranThumbnail);
                $ditulis['LokasiThumbnail'] = $lokasiThumbnail;
                $perubahan['LokasiThumbnail'] = $lokasiThumbnail;
            }

            // Baris yang sudah dihapus lunak ikut dipindah supaya pemulihannya tidak menunjuk salinan yang lenyap.
            DB::transaction(fn (): int => $saudara()->withTrashed()->update($perubahan));
        } catch (Throwable $galat) {
            foreach ($ditulis as $kolom => $lokasiDitulis) {
                $this->hapusBilaYatim($organisasiId, $disk, $kolom, $lokasiDitulis);
            }

            Log::warning('Pemadatan ulang berkas gagal; salinan lama dipertahankan.', [
                'organisasi' => $organisasiId,
                'lokasi' => $lokasiLama,
                'galat' => $galat->getMessage(),
            ]);

            return ['Hasil' => 'gagal', 'UkuranSesudah' => $hasil['UkuranSebelum'], 'Pesan' => $galat->getMessage()] + $hasil;
        } finally {
            $pemampatan?->bersihkan();
            if ($sementara !== null) {
                @unlink($sementara);
            }
        }

        if (($perubahan['LokasiPenyimpanan'] ?? $lokasiLama) !== $lokasiLama) {
            $this->hapusBilaYatim($organisasiId, $disk, 'LokasiPenyimpanan', $lokasiLama);
        }

        return $hasil;
    }

    private function salinKeSementara(Filesystem $media, string $lokasi): string
    {
        $sementara = tempnam(sys_get_temp_dir(), 'amanpoll-pampat-');
        if ($sementara === false) {
            throw new RuntimeException('Tidak dapat membuat berkas sementara.');
        }

        $masuk = $media->readStream($lokasi);
        $keluar = fopen($sementara, 'wb');

        try {
            if (! is_resource($masuk) || $keluar === false || stream_copy_to_stream($masuk, $keluar) === false) {
                throw new RuntimeException('Salinan fisik tidak dapat dibaca.');
            }
        } finally {
            if (is_resource($masuk)) {
                fclose($masuk);
            }
            if (is_resource($keluar)) {
                fclose($keluar);
            }
        }

        return $sementara;
    }

    /** Hash isi yang dibaca balik dari disk; gzip dibuka sehingga hasilnya hash isi asli. */
    private function hashTersimpan(Filesystem $media, string $lokasi, MetodeKompresi $metode): ?string
    {
        $aliran = $media->readStream($lokasi);
        if (! is_resource($aliran)) {
            return null;
        }

        try {
            if ($metode === MetodeKompresi::Gzip) {
                stream_filter_append($aliran, 'zlib.inflate', STREAM_FILTER_READ, ['window' => 31]);
            }

            $konteks = hash_init('sha256');
            hash_update_stream($konteks, $aliran);

            return hash_final($konteks);
        } finally {
            fclose($aliran);
        }
    }

    /**
     * @param  array{NamaAsli: string, DataTambahan: array<string, mixed>|null, DiunggahOleh: ?string}  $atribut
     */
    private function tulisDanCatat(HasilPemampatan $hasil, string $hash, string $disk, string $direktori, array $atribut): Berkas
    {
        $media = Storage::disk($disk);
        $kembar = Berkas::query()
            ->where('HashSha256', $hash)
            ->where('MetodeKompresi', $hasil->metode->value)
            ->where('MediaPenyimpanan', $disk)
            ->oldest('DibuatPada')
            ->first();

        $ulid = strtolower((string) Str::ulid());
        $lokasi = $kembar !== null ? $kembar->LokasiPenyimpanan : $direktori.'/'.$ulid.'.'.$hasil->ekstensi;
        $ditulis = [];

        try {
            // Salinan bersama yang hilang dari disk ditulis ulang di tempatnya,
            // sekaligus memulihkan baris lain yang merujuknya.
            if (! $media->exists($lokasi)) {
                $this->tulisAliran($media, $lokasi, $hasil->bukaIsi());
                $ditulis['LokasiPenyimpanan'] = $lokasi;
            }

            $lokasiThumbnail = null;
            if ($kembar?->LokasiThumbnail !== null && $media->exists($kembar->LokasiThumbnail)) {
                $lokasiThumbnail = $kembar->LokasiThumbnail;
            } elseif (($aliranThumbnail = $hasil->bukaThumbnail()) !== null) {
                $lokasiThumbnail = $direktori.'/thumbnail/'.$ulid.'.webp';
                $this->tulisAliran($media, $lokasiThumbnail, $aliranThumbnail);
                $ditulis['LokasiThumbnail'] = $lokasiThumbnail;
            }

            $ukuranTersimpan = isset($ditulis['LokasiPenyimpanan'])
                ? $hasil->ukuranTersimpan
                : ($kembar->UkuranTersimpanByte ?? $hasil->ukuranTersimpan);

            $berkas = new Berkas([
                ...$atribut,
                'NamaPenyimpanan' => basename($lokasi),
                'MediaPenyimpanan' => $disk,
                'LokasiPenyimpanan' => $lokasi,
                'JenisMime' => $hasil->jenisMime,
                'UkuranByte' => $hasil->metode === MetodeKompresi::Gzip ? $hasil->ukuranAsli : $ukuranTersimpan,
                'HashSha256' => $hash,
                'MetodeKompresi' => $hasil->metode,
                'UkuranAsliByte' => $hasil->ukuranAsli,
                'UkuranTersimpanByte' => $ukuranTersimpan,
                'LokasiThumbnail' => $lokasiThumbnail,
            ]);
            $berkas->save();

            return $berkas->refresh();
        } catch (Throwable $galat) {
            // Upload gagal tidak meninggalkan berkas fisik yatim (PRD 11).
            foreach ($ditulis as $kolom => $lokasiDitulis) {
                $this->hapusBilaYatim($this->konteks->wajibId(), $disk, $kolom, $lokasiDitulis);
            }

            throw $galat;
        }
    }

    /** @param resource $aliran */
    private function tulisAliran(Filesystem $media, string $lokasi, $aliran): void
    {
        try {
            if ($media->writeStream($lokasi, $aliran) === false) {
                throw new RuntimeException('Gagal menyimpan berkas.');
            }
        } finally {
            if (is_resource($aliran)) {
                fclose($aliran);
            }
        }
    }

    /** @param 'LokasiPenyimpanan'|'LokasiThumbnail' $kolom */
    private function hapusBilaYatim(string $organisasiId, string $disk, string $kolom, string $lokasi): void
    {
        $masihDirujuk = Berkas::query()
            ->withoutGlobalScope(ScopeOrganisasi::class)
            ->where('OrganisasiId', $organisasiId)
            ->where('MediaPenyimpanan', $disk)
            ->where($kolom, $lokasi)
            ->exists();

        if (! $masihDirujuk) {
            Storage::disk($disk)->delete($lokasi);
        }
    }

    /**
     * @template T
     *
     * @param  callable(): T  $aksi
     * @return T
     */
    private function dalamKunci(string $organisasiId, string $hash, callable $aksi): mixed
    {
        return Cache::lock('berkas-fisik:'.$organisasiId.':'.$hash, self::DETIK_KUNCI)
            ->block(self::DETIK_TUNGGU_KUNCI, $aksi);
    }
}
