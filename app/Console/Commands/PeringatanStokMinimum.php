<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Kirim notifikasi ke setiap pengguna berizin Stok.Kelola dalam organisasi yang punya suku cadang aktif. */
final class PeringatanStokMinimum extends Command
{
    protected $signature = 'suku-cadang:peringatan-stok-minimum';

    protected $description = 'Kirim notifikasi stok minimum untuk suku cadang yang stoknya sudah mencapai atau di bawah batas';

    public function __construct(private readonly LayananNotifikasi $layananNotifikasi)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $jumlahNotifikasi = 0;

        foreach (Organisasi::query()->cursor() as $organisasi) {
            $sukuCadangDiBawahMinimum = DB::table('SukuCadang as sc')
                ->leftJoin('StokSukuCadang as s', 's.SukuCadangId', '=', 'sc.Id')
                ->where('sc.OrganisasiId', $organisasi->Id)
                ->where('sc.Status', StatusSukuCadang::Aktif->value)
                ->whereNull('sc.DihapusPada')
                ->groupBy('sc.Id', 'sc.Nama', 'sc.Kode', 'sc.StokMinimum')
                ->havingRaw('COALESCE(SUM(s.JumlahTersedia), 0) - COALESCE(SUM(s.JumlahDitahan), 0) <= sc.StokMinimum')
                ->select('sc.Id', 'sc.Nama', 'sc.Kode')
                ->get();

            if ($sukuCadangDiBawahMinimum->isEmpty()) {
                continue;
            }

            $penggunaIdBerizin = $this->penggunaBerizinStokKelola($organisasi->Id);

            foreach ($penggunaIdBerizin as $penggunaId) {
                foreach ($sukuCadangDiBawahMinimum as $sukuCadang) {
                    $this->layananNotifikasi->kirim(
                        penggunaId: (string) $penggunaId,
                        jenisPeristiwa: 'Stok.MinimumTercapai',
                        isi: "Suku cadang {$sukuCadang->Nama} ({$sukuCadang->Kode}) mencapai atau di bawah stok minimum.",
                        judul: 'Peringatan Stok Minimum',
                        jenisEntitas: 'SukuCadang',
                        entitasId: (string) $sukuCadang->Id,
                    );
                    $jumlahNotifikasi++;
                }
            }
        }

        $this->info("Mengirim {$jumlahNotifikasi} notifikasi peringatan stok minimum.");

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function penggunaBerizinStokKelola(string $organisasiId): array
    {
        $penggunaId = DB::table('PenggunaPeran as pp')
            ->join('PeranIzin as pi', 'pi.PeranId', '=', 'pp.PeranId')
            ->join('Izin as i', 'i.Id', '=', 'pi.IzinId')
            ->where('pp.OrganisasiId', $organisasiId)
            ->where('i.Kode', 'Stok.Kelola')
            ->where(fn ($q) => $q->whereNull('pp.BerlakuMulai')->orWhere('pp.BerlakuMulai', '<=', now()))
            ->where(fn ($q) => $q->whereNull('pp.BerlakuSampai')->orWhere('pp.BerlakuSampai', '>=', now()))
            ->distinct()
            ->pluck('pp.PenggunaId');

        return array_values(array_map(strval(...), $penggunaId->all()));
    }
}
