<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Http\Requests\SimpanAsetRequest;
use App\Domain\Platform\Http\Requests\SimpanKonfigurasiOrganisasiRequest;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AturanWajibTest extends TestCase
{
    use RefreshDatabase;

    /**
     * FormRequest yang rules()-nya bergantung pada isi kiriman, bukan hanya
     * pada rute. Bentuk aturannya berubah menurut masukan, jadi tidak ada satu
     * jawaban "wajib atau tidak" yang dapat dipasang di label sebelum pengguna
     * mengetik. Halaman yang memakainya menandai sendiri secara manual.
     *
     * @var list<class-string<FormRequest>>
     */
    private const DIKECUALIKAN = [
        SimpanKonfigurasiOrganisasiRequest::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $organisasi = Organisasi::create(['Kode' => 'ORG-WJB', 'Nama' => 'Organisasi Wajib']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
    }

    public function test_membedakan_field_wajib_dari_yang_boleh_kosong(): void
    {
        $wajib = AturanWajib::untuk(SimpanAsetRequest::class);

        $this->assertTrue($wajib['Nama']);
        $this->assertTrue($wajib['KategoriAsetId']);
        $this->assertTrue($wajib['Status']);
        // Kode terbit otomatis, jadi tidak boleh bertanda bintang.
        $this->assertFalse($wajib['KodeAset']);
        $this->assertFalse($wajib['NomorSeri']);
        $this->assertFalse($wajib['PenyediaId']);
    }

    /**
     * Membaca aturan tidak boleh ikut menjalankan validasinya.
     *
     * FormRequest yang di-resolve lewat container akan memicu authorize() dan
     * validate() terhadap permintaan yang sedang berjalan -- pada halaman GET
     * itu berarti halamannya gagal dimuat, bukan sekadar bintangnya hilang.
     */
    public function test_membaca_aturan_tidak_memicu_validasi(): void
    {
        $wajib = AturanWajib::untuk(SimpanAsetRequest::class);

        $this->assertNotSame([], $wajib);
    }

    /** @return array<string, mixed> */
    public static function aturanMentah(): array
    {
        return [
            'required polos wajib' => [['required', 'string'], true],
            'string berpisah pipa' => ['required|max:10', true],
            'nullable tidak wajib' => [['nullable', 'string'], false],
            'sometimes tidak wajib' => [['sometimes', 'string'], false],
            // Bergantung pada field lain; bintangnya akan salah separuh waktu.
            'required_if tidak ditandai' => [['required_if:Tipe,Pilihan', 'nullable'], false],
            'required_without tidak ditandai' => [['required_without:Lain', 'nullable'], false],
            'required_with tidak ditandai' => [['nullable', 'required_with:Lain'], false],
        ];
    }

    #[DataProvider('aturanMentah')]
    public function test_hanya_required_polos_yang_dihitung_wajib(mixed $aturan, bool $diharapkan): void
    {
        $this->assertSame(['Bidang' => $diharapkan], AturanWajib::dariAturan(['Bidang' => $aturan]));
    }

    /** Field bersarang tidak punya satu label di layar, jadi tidak ikut dikirim. */
    public function test_field_bersarang_dilewati(): void
    {
        $hasil = AturanWajib::dariAturan([
            'Nama' => ['required'],
            'Detail' => ['required', 'array'],
            'Detail.*.Jumlah' => ['required', 'numeric'],
        ]);

        $this->assertSame(['Nama' => true, 'Detail' => true], $hasil);
    }

    /**
     * Penjaga rollout: tiap FormRequest harus dapat dibaca AturanWajib, supaya
     * memasangnya di halaman mana pun tidak pernah meledak saat dirender.
     */
    public function test_seluruh_form_request_dapat_dibaca(): void
    {
        // Seluruh halaman formulir ada di balik middleware auth, dan sebagian
        // rules() memang membaca penggunanya. Sweep ini harus meniru itu, bukan
        // membacanya sebagai tamu.
        $this->actingAs(Pengguna::create([
            'OrganisasiId' => app(KonteksOrganisasi::class)->id(),
            'Nama' => 'Penyapu',
            'Email' => 'penyapu@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]), 'web');

        $gagal = [];

        foreach ($this->daftarFormRequest() as $kelas) {
            if (in_array($kelas, self::DIKECUALIKAN, true)) {
                continue;
            }

            try {
                AturanWajib::untuk($kelas);
            } catch (\Throwable $e) {
                $gagal[$kelas] = $e->getMessage();
            }
        }

        $this->assertSame([], $gagal, 'FormRequest berikut tidak dapat dibaca AturanWajib.');
    }

    /**
     * Penjaga pemasangan: halaman yang sudah dipasangi harus benar-benar
     * mengirim petanya. Tanpa ini prop-nya dapat hilang saat controller diubah
     * dan bintangnya lenyap tanpa satu pun test merah.
     */
    public function test_halaman_yang_sudah_dipasangi_mengirim_peta_wajib(): void
    {
        $pengguna = $this->buatPenggunaBerizin(['Aset.Lihat', 'Penyedia.Kelola', 'Stok.Kelola', 'Pengguna.Kelola']);

        $halaman = [
            '/aset' => 'aset',
            '/penyedia' => 'penyedia',
            '/gudang' => 'gudang',
            '/platform/pengguna' => 'pengguna',
        ];

        foreach ($halaman as $jalur => $kunciFormulir) {
            $this->actingAs($pengguna)->get($jalur)
                ->assertOk()
                ->assertInertia(fn ($props) => $props
                    ->where("wajib.{$kunciFormulir}.Nama", true)
                    ->etc());
        }
    }

    /** @param  list<string>  $kodeIzin */
    private function buatPenggunaBerizin(array $kodeIzin): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => app(KonteksOrganisasi::class)->id(),
            'Nama' => 'Admin',
            'Email' => 'admin+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);

        foreach ($kodeIzin as $kode) {
            $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        }

        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }

    /** @return list<class-string<FormRequest>> */
    private function daftarFormRequest(): array
    {
        $kelas = [];

        foreach ((array) glob(base_path('app/Domain/*/Http/Requests/*.php')) as $berkas) {
            $relatif = substr((string) $berkas, strlen(base_path('app/Domain/')), -4);
            $nama = 'App\\Domain\\'.str_replace('/', '\\', $relatif);

            if (class_exists($nama) && is_subclass_of($nama, FormRequest::class)) {
                $kelas[] = $nama;
            }
        }

        return $kelas;
    }
}
