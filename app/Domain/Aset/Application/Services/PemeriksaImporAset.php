<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Services;

use App\Core\Izin\PemeriksaLingkupBaris;
use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Domain\ValueObjects\KolomImporAset;
use App\Domain\Aset\Http\Requests\SimpanAsetRequest;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Aspak\Infrastructure\Persistence\Models\AlkesAspak;
use App\Domain\Langganan\Application\Services\PenjagaBatasLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\LanggananTidakMengizinkan;
use App\Shared\Infrastructure\Impor\PembacaBerkasTabel;
use BackedEnum;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Memeriksa seluruh baris berkas impor aset tanpa menyimpan apa pun (PRD 8.4).
 *
 * Dipanggil dua kali dengan berkas yang sama -- saat pratinjau dan saat
 * konfirmasi -- supaya tidak ada data impor yang perlu disimpan di antara
 * kedua langkah, dan supaya yang dibuat adalah hasil pemeriksaan terbaru.
 *
 * Aturan per baris diambil dari `SimpanAsetRequest::rules()` apa adanya, bukan
 * disalin, jadi aturan formulir yang berubah otomatis berlaku di impor.
 * Di atasnya ditambah yang hanya bermakna untuk berkas: kode rujukan yang
 * tidak dikenal, kode aset kembar di dalam berkas, kode yang sudah dipakai
 * (termasuk aset yang diarsipkan, karena indeks unik basis data ikut
 * menghitungnya), dan lingkup akses pengguna.
 */
final class PemeriksaImporAset
{
    public const MAKS_BARIS = 1000;

    /** Isian yang menjadi dasar lingkup; galat lingkup hanya dilaporkan bila ketiganya sudah sah. */
    private const RUAS_LINGKUP = ['LokasiId', 'UnitOrganisasiId', 'UnitPengelolaId'];

    /** Isian ber-enum; nilai berkas dicocokkan tanpa peduli huruf besar dan spasi. */
    private const RUAS_ENUM = [
        'Status' => StatusAset::class,
        'Kondisi' => KondisiAset::class,
        'TingkatKritis' => TingkatKritisAset::class,
    ];

    private const RUAS_TANGGAL = ['TanggalPerolehan', 'TanggalMulaiOperasi', 'TanggalAkhirOperasi'];

    public function __construct(
        private readonly PembacaBerkasTabel $pembaca,
        private readonly PemeriksaLingkupBaris $lingkup,
        private readonly PenjagaBatasLangganan $penjagaBatas,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    /**
     * @param  'csv'|'xlsx'  $format
     * @return array{
     *     galatBerkas: list<string>,
     *     jumlahBaris: int,
     *     galat: list<array{baris: int, kolom: string, nilai: string, pesan: string}>,
     *     sah: list<array{baris: int, data: array<string, mixed>, tampilan: array{Kategori: string|null, Lokasi: string|null, UnitPengelola: string|null}}>
     * }
     */
    public function periksa(string $jalur, string $format, string $penggunaId): array
    {
        try {
            $berkas = $this->pembaca->baca($jalur, $format, self::MAKS_BARIS);
        } catch (AturanBisnisDilanggar $e) {
            return $this->ditolak([$e->getMessage()]);
        }

        [$petaKolom, $galatKepala] = $this->petakanKepala($berkas['kepala'], $berkas['baris']);

        if ($galatKepala !== []) {
            return $this->ditolak($galatKepala);
        }

        $jumlahBaris = count($berkas['baris']);

        if ($jumlahBaris === 0) {
            return $this->ditolak(['Berkas belum berisi baris data di bawah judul kolom.']);
        }

        try {
            $this->penjagaBatas->pastikanMasihMuat(KatalogFitur::BATAS_ASET, $jumlahBaris);
        } catch (LanggananTidakMengizinkan $e) {
            return $this->ditolak([$e->getMessage()." Berkas ini berisi {$jumlahBaris} aset."]);
        }

        $hasil = $this->periksaBaris($berkas['baris'], $petaKolom, $penggunaId);

        return ['galatBerkas' => [], 'jumlahBaris' => $jumlahBaris, 'galat' => $hasil['galat'], 'sah' => $hasil['sah']];
    }

    /**
     * Berkas yang ditolak utuh, sebelum satu baris pun diperiksa.
     *
     * @param  list<string>  $galatBerkas
     * @return array{galatBerkas: list<string>, jumlahBaris: int, galat: list<never>, sah: list<never>}
     */
    private function ditolak(array $galatBerkas): array
    {
        return ['galatBerkas' => $galatBerkas, 'jumlahBaris' => 0, 'galat' => [], 'sah' => []];
    }

    /**
     * @param  list<string>  $kepala
     * @param  list<array{nomor: int, sel: list<string>}>  $baris
     * @return array{0: array<int, string>, 1: list<string>} indeks kolom => ruas, dan galat kepala
     */
    private function petakanKepala(array $kepala, array $baris): array
    {
        $petaJudul = KolomImporAset::petaKepala();
        $judulPerRuas = KolomImporAset::judulPerRuas();
        $peta = [];
        $galat = [];

        foreach ($kepala as $indeks => $judul) {
            $normal = KolomImporAset::normalkan($judul);

            if ($normal === '') {
                $berisi = array_filter($baris, static fn (array $satu): bool => ($satu['sel'][$indeks] ?? '') !== '');

                if ($berisi !== []) {
                    $galat[] = sprintf('Kolom ke-%d tidak punya judul padahal berisi data.', $indeks + 1);
                }

                continue;
            }

            $ruas = $petaJudul[$normal] ?? null;

            if ($ruas === null) {
                $galat[] = "Kolom \"{$judul}\" tidak dikenali. Gunakan judul kolom dari templat.";

                continue;
            }

            if (in_array($ruas, $peta, true)) {
                $galat[] = "Kolom \"{$judulPerRuas[$ruas]}\" muncul lebih dari sekali.";

                continue;
            }

            $peta[$indeks] = $ruas;
        }

        foreach (KolomImporAset::RUAS_WAJIB as $ruas) {
            if (! in_array($ruas, $peta, true)) {
                $galat[] = "Kolom wajib \"{$judulPerRuas[$ruas]}\" tidak ada.";
            }
        }

        return [$peta, $galat];
    }

    /**
     * @param  list<array{nomor: int, sel: list<string>}>  $daftarBaris
     * @param  array<int, string>  $petaKolom
     * @return array{
     *     galat: list<array{baris: int, kolom: string, nilai: string, pesan: string}>,
     *     sah: list<array{baris: int, data: array<string, mixed>, tampilan: array{Kategori: string|null, Lokasi: string|null, UnitPengelola: string|null}}>
     * }
     */
    private function periksaBaris(array $daftarBaris, array $petaKolom, string $penggunaId): array
    {
        $judulPerRuas = KolomImporAset::judulPerRuas();
        $rujukanPerRuas = [];

        foreach (KolomImporAset::daftar() as $kolom) {
            if ($kolom['rujukan'] !== null) {
                $rujukanPerRuas[$kolom['ruas']] = $kolom['rujukan'];
            }
        }

        // Nilai mentah per baris, ruas => teks.
        $mentah = array_map(function (array $satu) use ($petaKolom): array {
            $nilai = [];

            foreach ($petaKolom as $indeks => $ruas) {
                $nilai[$ruas] = $satu['sel'][$indeks] ?? '';
            }

            return $nilai;
        }, $daftarBaris);

        $kamus = $this->kamusRujukan($mentah, $rujukanPerRuas);
        $kodeTerpakai = $this->kodeAsetTerpakai($mentah);

        // Dibaca sekali; objek aturannya (Rule::unique, UnitPengelolaSah) aman dipakai ulang tiap baris.
        $aturan = (new SimpanAsetRequest)->rules();

        $galat = [];
        $sah = [];
        $kodeDiBerkas = [];

        foreach ($daftarBaris as $urutan => $satu) {
            $nomor = $satu['nomor'];
            $nilai = $mentah[$urutan];
            // ruas => pesan; satu pesan per kolom, pesan pertama yang menang.
            $galatBaris = [];
            $data = [];
            $tampilan = ['Kategori' => null, 'Lokasi' => null, 'UnitPengelola' => null];

            foreach ($nilai as $ruas => $teks) {
                if (isset($rujukanPerRuas[$ruas])) {
                    $data[$ruas] = null;

                    if ($teks === '') {
                        continue;
                    }

                    $cocok = $kamus[$rujukanPerRuas[$ruas]][mb_strtolower($teks)] ?? [];

                    if ($cocok === []) {
                        $galatBaris[$ruas] = "{$judulPerRuas[$ruas]} \"{$teks}\" tidak ditemukan.";
                    } elseif (count($cocok) > 1) {
                        $galatBaris[$ruas] = "{$judulPerRuas[$ruas]} \"{$teks}\" dipakai lebih dari satu data; perbaiki kodenya di halaman master lebih dulu.";
                    } else {
                        $data[$ruas] = $cocok[0]['Id'];
                        $tampilan = $this->denganTampilan($tampilan, $ruas, $cocok[0]['Nama']);
                    }

                    continue;
                }

                $data[$ruas] = $this->nilaiIsian($ruas, $teks);
            }

            foreach (KolomImporAset::BAWAAN as $ruas => $bawaan) {
                $data[$ruas] ??= $bawaan;
            }

            $kodeAset = $data['KodeAset'] ?? null;

            if (is_string($kodeAset)) {
                $kunci = mb_strtolower($kodeAset);

                if (isset($kodeDiBerkas[$kunci])) {
                    $galatBaris['KodeAset'] = "Kode aset \"{$kodeAset}\" sudah dipakai di baris {$kodeDiBerkas[$kunci]}.";
                } elseif (isset($kodeTerpakai[$kunci])) {
                    $galatBaris['KodeAset'] = $kodeTerpakai[$kunci]
                        ? "Kode aset \"{$kodeAset}\" sudah dipakai aset yang diarsipkan."
                        : "Kode aset \"{$kodeAset}\" sudah dipakai aset lain.";
                }

                $kodeDiBerkas[$kunci] ??= $nomor;
            }

            $validator = Validator::make($data, $aturan, [], $judulPerRuas);

            foreach ($validator->errors()->messages() as $ruas => $pesan) {
                $galatBaris[$ruas] ??= $pesan[0];
            }

            if (array_intersect_key($galatBaris, array_flip(self::RUAS_LINGKUP)) === []
                && ! $this->dalamLingkup($penggunaId, $data)) {
                $galatBaris['LokasiId'] = 'Di luar lingkup akses Anda. Lokasi, unit organisasi, atau unit pengelolanya harus termasuk lingkup Anda.';
            }

            if ($galatBaris === []) {
                $sah[] = [
                    'baris' => $nomor,
                    // Isian kosong dibuang supaya bawaan basis data (mis. Mata Uang IDR) dan
                    // turunan kategori di BuatAset berlaku, sama seperti formulir yang tidak mengirimnya.
                    'data' => array_filter($data, static fn (mixed $isi): bool => $isi !== null),
                    'tampilan' => $tampilan,
                ];

                continue;
            }

            foreach ($this->urutkanMenurutKolom($galatBaris) as $ruas => $pesan) {
                $galat[] = [
                    'baris' => $nomor,
                    'kolom' => $judulPerRuas[$ruas] ?? $ruas,
                    'nilai' => $nilai[$ruas] ?? '',
                    'pesan' => $pesan,
                ];
            }
        }

        return ['galat' => $galat, 'sah' => $sah];
    }

    /**
     * Kode rujukan yang muncul di berkas, dicari sekali per jenis.
     *
     * Dibaca lewat model supaya tenancy berlaku -- kode organisasi lain tidak
     * pernah dikenali -- tetapi lepas dari ScopeLingkup, seperti `Rule::exists`
     * di formulir: lingkup diperiksa sendiri per baris dengan pesan yang jelas,
     * bukan disamarkan menjadi "kode tidak ditemukan".
     *
     * @param  list<array<string, string>>  $mentah
     * @param  array<string, string>  $rujukanPerRuas
     * @return array<string, array<string, list<array{Id: string, Nama: string}>>> jenis => kode kecil => cocok
     */
    private function kamusRujukan(array $mentah, array $rujukanPerRuas): array
    {
        $kodePerJenis = [];

        foreach ($mentah as $nilai) {
            foreach ($rujukanPerRuas as $ruas => $jenis) {
                $kode = $nilai[$ruas] ?? '';

                if ($kode !== '') {
                    $kodePerJenis[$jenis][mb_strtolower($kode)] = $kode;
                }
            }
        }

        $kamus = [];

        foreach ($kodePerJenis as $jenis => $kode) {
            [$kueri, $kolomKode] = $this->kueriRujukan($jenis);

            foreach ($kueri->whereIn($kolomKode, array_values($kode))->get(['Id', $kolomKode, 'Nama']) as $baris) {
                $kamus[$jenis][mb_strtolower((string) $baris->getAttribute($kolomKode))][] = [
                    'Id' => (string) $baris->getKey(),
                    'Nama' => (string) $baris->getAttribute('Nama'),
                ];
            }
        }

        return $kamus;
    }

    /**
     * @return array{0: Builder<covariant Model>, 1: string}
     */
    private function kueriRujukan(string $jenis): array
    {
        return match ($jenis) {
            KolomImporAset::RUJUKAN_KATEGORI => [KategoriAset::query(), 'Kode'],
            KolomImporAset::RUJUKAN_MODEL => [ModelAset::query(), 'KodeModel'],
            KolomImporAset::RUJUKAN_ASPAK => [AlkesAspak::query(), 'Kode'],
            KolomImporAset::RUJUKAN_PENYEDIA => [Penyedia::query(), 'Kode'],
            KolomImporAset::RUJUKAN_LOKASI => [Lokasi::query()->withoutGlobalScope(ScopeLingkup::class), 'Kode'],
            // Unit pengelola dicari di tabel yang sama; sah-tidaknya sebagai unit
            // pengelola diputuskan UnitPengelolaSah lewat aturan formulir.
            default => [UnitOrganisasi::query()->withoutGlobalScope(ScopeLingkup::class), 'Kode'],
        };
    }

    /**
     * Kode aset di berkas yang sudah dipakai di organisasi ini.
     *
     * Termasuk aset yang diarsipkan (soft delete): `Rule::unique` di formulir
     * melewatkannya, tetapi indeks unik `UqAsetKode` tidak, sehingga tanpa
     * pemeriksaan ini transaksi impor gagal di tengah jalan.
     *
     * @param  list<array<string, string>>  $mentah
     * @return array<string, bool> kode kecil => diarsipkan
     */
    private function kodeAsetTerpakai(array $mentah): array
    {
        $kode = array_values(array_unique(array_filter(array_map(
            static fn (array $nilai): string => $nilai['KodeAset'] ?? '',
            $mentah,
        ))));

        if ($kode === []) {
            return [];
        }

        $hasil = [];

        foreach (DB::table('Aset')
            ->where('OrganisasiId', $this->konteks->wajibId())
            ->whereIn('KodeAset', $kode)
            ->get(['KodeAset', 'DihapusPada']) as $baris) {
            $hasil[mb_strtolower((string) $baris->KodeAset)] = $baris->DihapusPada !== null;
        }

        return $hasil;
    }

    /** Teks sel menjadi nilai isian formulir: kosong menjadi null, enum dan tanggal dirapikan. */
    private function nilaiIsian(string $ruas, string $teks): ?string
    {
        if ($teks === '') {
            return null;
        }

        if (isset(self::RUAS_ENUM[$ruas])) {
            return $this->nilaiEnum(self::RUAS_ENUM[$ruas], $teks);
        }

        if (in_array($ruas, self::RUAS_TANGGAL, true) && ctype_digit($teks) && (int) $teks >= 1 && (int) $teks <= 2958465) {
            // Nomor seri tanggal Excel: sel tanggal yang diformat "General"
            // terbaca sebagai angka, mis. 45443 untuk 31 Mei 2024.
            return (new DateTimeImmutable('1899-12-30'))->modify('+'.(int) $teks.' days')->format('Y-m-d');
        }

        if (in_array($ruas, self::RUAS_TANGGAL, true)
            && preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/', $teks, $bagian) === 1) {
            // Tanggal gaya Indonesia (hari/bulan/tahun). Tanpa ini "05/12/2024"
            // dibaca PHP sebagai 12 Mei, bukan 5 Desember.
            return checkdate((int) $bagian[2], (int) $bagian[1], (int) $bagian[3])
                ? sprintf('%04d-%02d-%02d', $bagian[3], $bagian[2], $bagian[1])
                : $teks;
        }

        if ($ruas === 'MataUang') {
            return mb_strtoupper($teks);
        }

        return $teks;
    }

    /**
     * "perlu perhatian" menjadi `PerluPerhatian`; nilai yang tidak dikenal
     * diteruskan apa adanya supaya aturan enum formulir yang menolaknya.
     *
     * @param  class-string<BackedEnum>  $enum
     */
    private function nilaiEnum(string $enum, string $teks): string
    {
        $normal = KolomImporAset::normalkan($teks);

        foreach ($enum::cases() as $kasus) {
            if (KolomImporAset::normalkan((string) $kasus->value) === $normal) {
                return (string) $kasus->value;
            }
        }

        return $teks;
    }

    /**
     * Nama rujukan untuk contoh baris di pratinjau.
     *
     * @param  array{Kategori: string|null, Lokasi: string|null, UnitPengelola: string|null}  $tampilan
     * @return array{Kategori: string|null, Lokasi: string|null, UnitPengelola: string|null}
     */
    private function denganTampilan(array $tampilan, string $ruas, string $nama): array
    {
        return match ($ruas) {
            'KategoriAsetId' => [...$tampilan, 'Kategori' => $nama],
            'LokasiId' => [...$tampilan, 'Lokasi' => $nama],
            'UnitPengelolaId' => [...$tampilan, 'UnitPengelola' => $nama],
            default => $tampilan,
        };
    }

    /**
     * Baris akan terlihat oleh pengguna sesudah dibuat: semantik ScopeLingkup
     * yang sama dengan daftar aset, jadi staf berlingkup unit IT boleh
     * mengimpor aset kelolaan IT di ruangan mana pun (PRD 8.21), tetapi staf
     * berlingkup satu ruangan tidak bisa mengimpor ke ruangan lain.
     *
     * @param  array<string, mixed>  $data
     */
    private function dalamLingkup(string $penggunaId, array $data): bool
    {
        $calon = new Aset;
        $calon->setAttribute('OrganisasiId', $this->konteks->wajibId());

        foreach (self::RUAS_LINGKUP as $ruas) {
            $calon->setAttribute($ruas, $data[$ruas] ?? null);
        }

        return $this->lingkup->mencakup($penggunaId, $calon);
    }

    /**
     * @param  array<string, string>  $galatBaris
     * @return array<string, string>
     */
    private function urutkanMenurutKolom(array $galatBaris): array
    {
        $urutan = array_flip(array_keys(KolomImporAset::judulPerRuas()));

        uksort($galatBaris, static fn (string $a, string $b): int => ($urutan[$a] ?? PHP_INT_MAX) <=> ($urutan[$b] ?? PHP_INT_MAX));

        return $galatBaris;
    }
}
