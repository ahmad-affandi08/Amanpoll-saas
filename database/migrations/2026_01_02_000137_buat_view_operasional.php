<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** View operasional MySQL. */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW `ViewRingkasanAset` AS
SELECT
  a.`OrganisasiId`,
  a.`Id` AS `AsetId`,
  a.`KodeAset`,
  a.`Nama`,
  a.`Status`,
  a.`Kondisi`,
  a.`TingkatKritis`,
  ka.`Nama` AS `Kategori`,
  ma.`Nama` AS `Model`,
  m.`Nama` AS `Merek`,
  l.`Nama` AS `Lokasi`,
  u.`Nama` AS `UnitOrganisasi`,
  a.`HargaPerolehan`,
  a.`TanggalPerolehan`
FROM `Aset` a
JOIN `KategoriAset` ka ON ka.`Id` = a.`KategoriAsetId`
LEFT JOIN `ModelAset` ma ON ma.`Id` = a.`ModelAsetId`
LEFT JOIN `Merek` m ON m.`Id` = ma.`MerekId`
LEFT JOIN `Lokasi` l ON l.`Id` = a.`LokasiId`
LEFT JOIN `UnitOrganisasi` u ON u.`Id` = a.`UnitOrganisasiId`
WHERE a.`DihapusPada` IS NULL
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW `ViewStokSukuCadang` AS
SELECT
  s.`OrganisasiId`,
  s.`Id` AS `SukuCadangId`,
  s.`Kode`,
  s.`Nama`,
  s.`SatuanDasar`,
  s.`StokMinimum`,
  COALESCE(SUM(st.`JumlahTersedia`),0) AS `JumlahTersedia`,
  COALESCE(SUM(st.`JumlahDitahan`),0) AS `JumlahDitahan`,
  COALESCE(SUM(st.`JumlahDipesan`),0) AS `JumlahDipesan`
FROM `SukuCadang` s
LEFT JOIN `StokSukuCadang` st ON st.`SukuCadangId` = s.`Id`
WHERE s.`DihapusPada` IS NULL
GROUP BY s.`OrganisasiId`, s.`Id`, s.`Kode`, s.`Nama`, s.`SatuanDasar`, s.`StokMinimum`
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW `ViewKinerjaPerintahKerja` AS
SELECT
  pk.`OrganisasiId`,
  pk.`Id` AS `PerintahKerjaId`,
  pk.`Nomor`,
  pk.`Jenis`,
  pk.`Prioritas`,
  pk.`Status`,
  pk.`DijadwalkanMulaiPada`,
  pk.`DiterimaPada`,
  pk.`DimulaiPada`,
  pk.`DiselesaikanPada`,
  CASE
    WHEN pk.`DiterimaPada` IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, pk.`DibuatPada`, pk.`DiterimaPada`)
    ELSE NULL
  END AS `MenitRespons`,
  CASE
    WHEN pk.`DimulaiPada` IS NOT NULL AND pk.`DiselesaikanPada` IS NOT NULL
      THEN TIMESTAMPDIFF(MINUTE, pk.`DimulaiPada`, pk.`DiselesaikanPada`)
    ELSE NULL
  END AS `MenitPengerjaan`,
  CASE
    WHEN pk.`BatasPenyelesaianPada` IS NOT NULL AND pk.`DiselesaikanPada` IS NOT NULL
      THEN (pk.`DiselesaikanPada` <= pk.`BatasPenyelesaianPada`)
    ELSE NULL
  END AS `MemenuhiTingkatLayanan`
FROM `PerintahKerja` pk
WHERE pk.`DihapusPada` IS NULL
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW `ViewKepatuhanKalibrasi` AS
SELECT
  a.`OrganisasiId`,
  a.`Id` AS `AsetId`,
  a.`KodeAset`,
  a.`Nama`,
  rk.`TanggalBerikutnya`,
  CASE
    WHEN rk.`Aktif` = 0 THEN 'TidakAktif'
    WHEN rk.`TanggalBerikutnya` < CURRENT_DATE THEN 'Terlambat'
    WHEN rk.`TanggalBerikutnya` <= DATE_ADD(CURRENT_DATE, INTERVAL rk.`PeringatanHariSebelum` DAY) THEN 'SegeraJatuhTempo'
    ELSE 'Valid'
  END AS `StatusKalibrasi`
FROM `Aset` a
JOIN `RencanaKalibrasi` rk ON rk.`AsetId` = a.`Id`
WHERE a.`DihapusPada` IS NULL
SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('DROP VIEW IF EXISTS `ViewRingkasanAset`');
        DB::statement('DROP VIEW IF EXISTS `ViewStokSukuCadang`');
        DB::statement('DROP VIEW IF EXISTS `ViewKinerjaPerintahKerja`');
        DB::statement('DROP VIEW IF EXISTS `ViewKepatuhanKalibrasi`');
    }
};
