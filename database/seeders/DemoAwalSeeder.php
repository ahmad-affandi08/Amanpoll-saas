<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Data demo satu perusahaan lengkap: PT Sinar Nusantara Industri (kode SNI),
 * produsen kemasan plastik dengan kantor pusat Jakarta, pabrik Cikarang, dan
 * gudang distribusi Surabaya, beserta riwayat operasional setahun terakhir.
 *
 * Seeder ini menolak berjalan di produksi. Peringatan di runbook sudah ada
 * sejak lama, tetapi `php artisan db:seed --force` adalah perintah refleks dan
 * prosa tidak menahan siapa pun yang sedang terburu-buru; yang ditanamnya
 * bukan sekadar data contoh melainkan akun Super Admin berkata sandi yang
 * dapat ditebak.
 *
 * Fondasi dan master idempoten. Seeder transaksi melewati dirinya bila datanya
 * sudah ada, jadi riwayat lengkap hanya terbentuk pada basis data kosong
 * (`php artisan migrate:fresh --seed`).
 */
final class DemoAwalSeeder extends Seeder
{
    use KonteksDemo;

    /** @var list<class-string<Seeder>> Organisasi, pengguna, dan master; idempoten. */
    private const FONDASI = [
        DemoFondasiSeeder::class,
        DemoMasterSeeder::class,
    ];

    /**
     * Riwayat operasional setahun lewat Action resmi. Urutan mengikat: setiap seeder
     * memakai hasil seeder sebelumnya (perintah kerja, stok, alur persetujuan).
     *
     * @var list<class-string<Seeder>>
     */
    private const RIWAYAT = [
        DemoPemeliharaanSeeder::class,
        DemoPreventifKalibrasiSeeder::class,
        DemoPengadaanSeeder::class,
        DemoKepatuhanSiklusSeeder::class,
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'DemoAwalSeeder tidak boleh dijalankan di produksi: isinya organisasi, '
                .'aset, dan akun Super Admin contoh. Semai kunci wajibnya satu per satu '
                .'(IzinSeeder, FiturPaketSeeder, FiturPlatformSeeder, TahapPipelineSeeder).',
            );
        }

        $this->call(self::FONDASI);

        if (! config('amanpoll.demo.riwayat')) {
            return;
        }

        $this->call(self::RIWAYAT);
        $this->rapikanNotifikasi();
    }

    /**
     * Action riwayat mengantrekan notifikasi in-app bertanggal mundur. Tanpa dirapikan,
     * worker pertama menandai ratusan notifikasi setahun lalu sebagai baru sekaligus.
     * Notifikasi diberi waktu kirim sesuai kejadiannya, yang lebih tua dari tiga hari
     * dianggap sudah dibaca, dan pekerjaan antreannya dibuang karena tak ada lagi yang dikirim.
     */
    private function rapikanNotifikasi(): void
    {
        $antri = DB::table('Notifikasi')
            ->where('OrganisasiId', $this->organisasiId())
            ->where('Kanal', 'InApp')
            ->where('Status', 'Antri');

        foreach ((clone $antri)->pluck('Id') as $notifikasiId) {
            DB::table('AntrianPekerjaan')
                ->where('payload', 'like', '%KirimNotifikasi%')
                ->where('payload', 'like', '%'.$notifikasiId.'%')
                ->delete();
        }

        $batasDibaca = now()->subDays(3);

        (clone $antri)->where('DibuatPada', '<', $batasDibaca)->update([
            'Status' => 'Terkirim',
            'DikirimPada' => DB::raw('DibuatPada'),
            'DibacaPada' => DB::raw('DATE_ADD(DibuatPada, INTERVAL 2 HOUR)'),
        ]);

        (clone $antri)->update([
            'Status' => 'Terkirim',
            'DikirimPada' => DB::raw('DibuatPada'),
        ]);
    }
}
