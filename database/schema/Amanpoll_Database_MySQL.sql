-- ============================================================
-- AMANPOLL - Database Utama
-- Target: MySQL 8.0+
-- Konvensi:
--   * Seluruh nama tabel/kolom menggunakan Bahasa Indonesia + PascalCase.
--   * Primary key memakai ULID CHAR(26), dibuat di layer aplikasi (Laravel HasUlids).
--   * Hampir seluruh data bisnis membawa OrganisasiId untuk isolasi multi-organisasi.
--   * Waktu disimpan dalam UTC; timezone tenant/lokasi disimpan terpisah.
--   * Soft delete memakai DihapusPada.
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `Amanpoll`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE `Amanpoll`;

-- ============================================================
-- 01. PLATFORM, TENANCY, IAM
-- ============================================================

CREATE TABLE `Organisasi` (
  `Id` CHAR(26) NOT NULL,
  `Kode` VARCHAR(50) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `NamaLegal` VARCHAR(220) NULL,
  `JenisUsaha` VARCHAR(100) NULL,
  `NomorIdentitasPajak` VARCHAR(100) NULL,
  `Email` VARCHAR(180) NULL,
  `Telepon` VARCHAR(50) NULL,
  `Alamat` TEXT NULL,
  `Negara` VARCHAR(100) NULL,
  `Provinsi` VARCHAR(120) NULL,
  `Kota` VARCHAR(120) NULL,
  `ZonaWaktu` VARCHAR(64) NOT NULL DEFAULT 'Asia/Jakarta',
  `LogoUrl` TEXT NULL,
  `Status` VARCHAR(30) NOT NULL DEFAULT 'Aktif',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqOrganisasiKode` (`Kode`),
  KEY `IdxOrganisasiStatus` (`Status`)
) ENGINE=InnoDB;

CREATE TABLE `UnitOrganisasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `IndukId` CHAR(26) NULL,
  `Kode` VARCHAR(50) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Jenis` VARCHAR(60) NOT NULL DEFAULT 'Unit',
  `Email` VARCHAR(180) NULL,
  `Telepon` VARCHAR(50) NULL,
  `Status` VARCHAR(30) NOT NULL DEFAULT 'Aktif',
  `Urutan` INT NOT NULL DEFAULT 0,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqUnitOrganisasiKode` (`OrganisasiId`,`Kode`),
  KEY `IdxUnitOrganisasiInduk` (`IndukId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`IndukId`) REFERENCES `UnitOrganisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KategoriLokasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(50) NOT NULL,
  `Nama` VARCHAR(120) NOT NULL,
  `Keterangan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKategoriLokasiKode` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Lokasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `UnitOrganisasiId` CHAR(26) NULL,
  `KategoriLokasiId` CHAR(26) NULL,
  `IndukId` CHAR(26) NULL,
  `Kode` VARCHAR(60) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Alamat` TEXT NULL,
  `Lantai` VARCHAR(30) NULL,
  `Latitude` DECIMAL(10,7) NULL,
  `Longitude` DECIMAL(10,7) NULL,
  `ZonaWaktu` VARCHAR(64) NULL,
  `Status` VARCHAR(30) NOT NULL DEFAULT 'Aktif',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqLokasiKode` (`OrganisasiId`,`Kode`),
  KEY `IdxLokasiInduk` (`IndukId`),
  KEY `IdxLokasiUnit` (`UnitOrganisasiId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`UnitOrganisasiId`) REFERENCES `UnitOrganisasi` (`Id`),
  FOREIGN KEY (`KategoriLokasiId`) REFERENCES `KategoriLokasi` (`Id`),
  FOREIGN KEY (`IndukId`) REFERENCES `Lokasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Pengguna` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `UnitOrganisasiId` CHAR(26) NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Email` VARCHAR(180) NOT NULL,
  `Telepon` VARCHAR(50) NULL,
  `KataSandi` VARCHAR(255) NULL,
  `EmailTerverifikasiPada` DATETIME(6) NULL,
  `AvatarUrl` TEXT NULL,
  `NomorPegawai` VARCHAR(80) NULL,
  `Jabatan` VARCHAR(120) NULL,
  `JenisPengguna` VARCHAR(40) NOT NULL DEFAULT 'Internal',
  `Status` VARCHAR(30) NOT NULL DEFAULT 'Aktif',
  `TerakhirMasukPada` DATETIME(6) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPenggunaEmail` (`OrganisasiId`,`Email`),
  KEY `IdxPenggunaUnit` (`UnitOrganisasiId`),
  KEY `IdxPenggunaStatus` (`OrganisasiId`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`UnitOrganisasiId`) REFERENCES `UnitOrganisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Peran` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(120) NOT NULL,
  `Keterangan` TEXT NULL,
  `BawaanSistem` TINYINT(1) NOT NULL DEFAULT 0,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  KEY `IdxPeranOrganisasiKode` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Izin` (
  `Id` CHAR(26) NOT NULL,
  `Kode` VARCHAR(120) NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `Modul` VARCHAR(100) NOT NULL,
  `Keterangan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqIzinKode` (`Kode`)
) ENGINE=InnoDB;

CREATE TABLE `PenggunaPeran` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PenggunaId` CHAR(26) NOT NULL,
  `PeranId` CHAR(26) NOT NULL,
  `UnitOrganisasiId` CHAR(26) NULL,
  `LokasiId` CHAR(26) NULL,
  `BerlakuMulai` DATETIME(6) NULL,
  `BerlakuSampai` DATETIME(6) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPenggunaPeranScope` (`PenggunaId`,`PeranId`,`UnitOrganisasiId`,`LokasiId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`),
  FOREIGN KEY (`PeranId`) REFERENCES `Peran` (`Id`),
  FOREIGN KEY (`UnitOrganisasiId`) REFERENCES `UnitOrganisasi` (`Id`),
  FOREIGN KEY (`LokasiId`) REFERENCES `Lokasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PeranIzin` (
  `Id` CHAR(26) NOT NULL,
  `PeranId` CHAR(26) NOT NULL,
  `IzinId` CHAR(26) NOT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPeranIzin` (`PeranId`,`IzinId`),
  FOREIGN KEY (`PeranId`) REFERENCES `Peran` (`Id`),
  FOREIGN KEY (`IzinId`) REFERENCES `Izin` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PerangkatPengguna` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PenggunaId` CHAR(26) NOT NULL,
  `NamaPerangkat` VARCHAR(180) NULL,
  `Platform` VARCHAR(60) NULL,
  `IdentitasPerangkat` VARCHAR(255) NULL,
  `TokenPush` TEXT NULL,
  `TerakhirSinkronPada` DATETIME(6) NULL,
  `Status` VARCHAR(30) NOT NULL DEFAULT 'Aktif',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxPerangkatPengguna` (`PenggunaId`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KunciApi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nama` VARCHAR(150) NOT NULL,
  `AwalanKunci` VARCHAR(20) NOT NULL,
  `HashKunci` VARCHAR(255) NOT NULL,
  `Cakupan` JSON NULL,
  `AlamatIpDiizinkan` JSON NULL,
  `KadaluarsaPada` DATETIME(6) NULL,
  `TerakhirDipakaiPada` DATETIME(6) NULL,
  `Status` VARCHAR(30) NOT NULL DEFAULT 'Aktif',
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKunciApiPrefix` (`AwalanKunci`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KonfigurasiOrganisasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kunci` VARCHAR(160) NOT NULL,
  `Nilai` JSON NULL,
  `Rahasia` TINYINT(1) NOT NULL DEFAULT 0,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKonfigurasiOrganisasi` (`OrganisasiId`,`Kunci`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `NomorDokumen` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `JenisDokumen` VARCHAR(80) NOT NULL,
  `Awalan` VARCHAR(40) NULL,
  `FormatNomor` VARCHAR(160) NOT NULL,
  `NomorTerakhir` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `ResetPeriode` VARCHAR(30) NOT NULL DEFAULT 'Tahunan',
  `PeriodeAktif` VARCHAR(20) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqNomorDokumen` (`OrganisasiId`,`JenisDokumen`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `HariLibur` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `LokasiId` CHAR(26) NULL,
  `Tanggal` DATE NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `BerulangTahunan` TINYINT(1) NOT NULL DEFAULT 0,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqHariLibur` (`OrganisasiId`,`LokasiId`,`Tanggal`,`Nama`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`LokasiId`) REFERENCES `Lokasi` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 02. BERKAS, TAG, FIELD KUSTOM, KOMENTAR
-- ============================================================

CREATE TABLE `Berkas` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `NamaAsli` VARCHAR(255) NOT NULL,
  `NamaPenyimpanan` VARCHAR(255) NOT NULL,
  `MediaPenyimpanan` VARCHAR(60) NOT NULL DEFAULT 'private',
  `LokasiPenyimpanan` TEXT NOT NULL,
  `JenisMime` VARCHAR(120) NULL,
  `UkuranByte` BIGINT UNSIGNED NULL,
  `HashSha256` CHAR(64) NULL,
  `DataTambahan` JSON NULL,
  `DiunggahOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  KEY `IdxBerkasOrganisasiHash` (`OrganisasiId`,`HashSha256`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`DiunggahOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `LampiranEntitas` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `JenisEntitas` VARCHAR(80) NOT NULL,
  `EntitasId` CHAR(26) NOT NULL,
  `BerkasId` CHAR(26) NOT NULL,
  `Kategori` VARCHAR(80) NULL,
  `Keterangan` TEXT NULL,
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxLampiranEntitas` (`OrganisasiId`,`JenisEntitas`,`EntitasId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`BerkasId`) REFERENCES `Berkas` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Tag` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nama` VARCHAR(100) NOT NULL,
  `Warna` VARCHAR(20) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqTagNama` (`OrganisasiId`,`Nama`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `EntitasTag` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `TagId` CHAR(26) NOT NULL,
  `JenisEntitas` VARCHAR(80) NOT NULL,
  `EntitasId` CHAR(26) NOT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqEntitasTag` (`TagId`,`JenisEntitas`,`EntitasId`),
  KEY `IdxEntitasTagEntitas` (`OrganisasiId`,`JenisEntitas`,`EntitasId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`TagId`) REFERENCES `Tag` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DefinisiKolomKustom` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `JenisEntitas` VARCHAR(80) NOT NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Label` VARCHAR(160) NOT NULL,
  `TipeData` VARCHAR(40) NOT NULL,
  `Wajib` TINYINT(1) NOT NULL DEFAULT 0,
  `Pilihan` JSON NULL,
  `AturanValidasi` JSON NULL,
  `NilaiBawaan` JSON NULL,
  `Urutan` INT NOT NULL DEFAULT 0,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKolomKustom` (`OrganisasiId`,`JenisEntitas`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `NilaiKolomKustom` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `DefinisiKolomKustomId` CHAR(26) NOT NULL,
  `JenisEntitas` VARCHAR(80) NOT NULL,
  `EntitasId` CHAR(26) NOT NULL,
  `Nilai` JSON NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqNilaiKolomKustom` (`DefinisiKolomKustomId`,`JenisEntitas`,`EntitasId`),
  KEY `IdxNilaiKolomKustomEntitas` (`OrganisasiId`,`JenisEntitas`,`EntitasId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`DefinisiKolomKustomId`) REFERENCES `DefinisiKolomKustom` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KomentarEntitas` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `JenisEntitas` VARCHAR(80) NOT NULL,
  `EntitasId` CHAR(26) NOT NULL,
  `IndukKomentarId` CHAR(26) NULL,
  `Isi` TEXT NOT NULL,
  `DibuatOleh` CHAR(26) NOT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  KEY `IdxKomentarEntitas` (`OrganisasiId`,`JenisEntitas`,`EntitasId`,`DibuatPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`IndukKomentarId`) REFERENCES `KomentarEntitas` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 03. VENDOR, MITRA, KONTAK
-- ============================================================

CREATE TABLE `KategoriPenyedia` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(50) NOT NULL,
  `Nama` VARCHAR(120) NOT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKategoriPenyedia` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Penyedia` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(60) NOT NULL,
  `Nama` VARCHAR(200) NOT NULL,
  `NamaLegal` VARCHAR(240) NULL,
  `NomorIdentitasPajak` VARCHAR(100) NULL,
  `Email` VARCHAR(180) NULL,
  `Telepon` VARCHAR(60) NULL,
  `Website` VARCHAR(255) NULL,
  `Alamat` TEXT NULL,
  `Kota` VARCHAR(120) NULL,
  `Provinsi` VARCHAR(120) NULL,
  `Negara` VARCHAR(100) NULL,
  `Status` VARCHAR(30) NOT NULL DEFAULT 'Aktif',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPenyediaKode` (`OrganisasiId`,`Kode`),
  KEY `IdxPenyediaNama` (`OrganisasiId`,`Nama`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PenyediaKategori` (
  `Id` CHAR(26) NOT NULL,
  `PenyediaId` CHAR(26) NOT NULL,
  `KategoriPenyediaId` CHAR(26) NOT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPenyediaKategori` (`PenyediaId`,`KategoriPenyediaId`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`),
  FOREIGN KEY (`KategoriPenyediaId`) REFERENCES `KategoriPenyedia` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KontakPenyedia` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PenyediaId` CHAR(26) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Jabatan` VARCHAR(120) NULL,
  `Email` VARCHAR(180) NULL,
  `Telepon` VARCHAR(60) NULL,
  `Utama` TINYINT(1) NOT NULL DEFAULT 0,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxKontakPenyedia` (`PenyediaId`,`Utama`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PenilaianPenyedia` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PenyediaId` CHAR(26) NOT NULL,
  `PeriodeMulai` DATE NOT NULL,
  `PeriodeSelesai` DATE NOT NULL,
  `SkorKualitas` DECIMAL(5,2) NULL,
  `SkorKetepatanWaktu` DECIMAL(5,2) NULL,
  `SkorHarga` DECIMAL(5,2) NULL,
  `SkorLayanan` DECIMAL(5,2) NULL,
  `SkorTotal` DECIMAL(5,2) NULL,
  `Catatan` TEXT NULL,
  `DinilaiOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxPenilaianPenyedia` (`PenyediaId`,`PeriodeMulai`,`PeriodeSelesai`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`),
  FOREIGN KEY (`DinilaiOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 04. MASTER ASET & ASET
-- ============================================================

CREATE TABLE `KategoriAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `IndukId` CHAR(26) NULL,
  `Kode` VARCHAR(60) NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `UmurManfaatBulan` INT UNSIGNED NULL,
  `MetodePenyusutanBawaan` VARCHAR(40) NULL,
  `PersentaseNilaiResidu` DECIMAL(8,4) NULL,
  `MemerlukanKalibrasi` TINYINT(1) NOT NULL DEFAULT 0,
  `MemerlukanPemeliharaan` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKategoriAset` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`IndukId`) REFERENCES `KategoriAset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Merek` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `NegaraAsal` VARCHAR(100) NULL,
  `Website` VARCHAR(255) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxMerekNama` (`Nama`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `ModelAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `KategoriAsetId` CHAR(26) NOT NULL,
  `MerekId` CHAR(26) NULL,
  `KodeModel` VARCHAR(100) NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Produsen` VARCHAR(180) NULL,
  `Spesifikasi` JSON NULL,
  `IntervalPemeliharaanHari` INT UNSIGNED NULL,
  `IntervalKalibrasiHari` INT UNSIGNED NULL,
  `UmurManfaatBulan` INT UNSIGNED NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  KEY `IdxModelAsetKategori` (`OrganisasiId`,`KategoriAsetId`),
  KEY `IdxModelAsetMerek` (`MerekId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`KategoriAsetId`) REFERENCES `KategoriAset` (`Id`),
  FOREIGN KEY (`MerekId`) REFERENCES `Merek` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Aset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `UnitOrganisasiId` CHAR(26) NULL,
  `LokasiId` CHAR(26) NULL,
  `KategoriAsetId` CHAR(26) NOT NULL,
  `ModelAsetId` CHAR(26) NULL,
  `PenyediaId` CHAR(26) NULL,
  `KodeAset` VARCHAR(100) NOT NULL,
  `Nama` VARCHAR(200) NOT NULL,
  `NomorSeri` VARCHAR(160) NULL,
  `NomorInventaris` VARCHAR(160) NULL,
  `NomorRegistrasiEksternal` VARCHAR(160) NULL,
  `TanggalPerolehan` DATE NULL,
  `TanggalMulaiOperasi` DATE NULL,
  `TanggalAkhirOperasi` DATE NULL,
  `HargaPerolehan` DECIMAL(20,2) NULL,
  `NilaiResidu` DECIMAL(20,2) NULL,
  `MataUang` CHAR(3) NOT NULL DEFAULT 'IDR',
  `SumberDana` VARCHAR(120) NULL,
  `MetodePenyusutan` VARCHAR(40) NULL,
  `UmurManfaatBulan` INT UNSIGNED NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Aktif',
  `Kondisi` VARCHAR(40) NOT NULL DEFAULT 'Baik',
  `TingkatKritis` VARCHAR(30) NOT NULL DEFAULT 'Normal',
  `KodeQr` VARCHAR(255) NULL,
  `NfcUid` VARCHAR(255) NULL,
  `KodeBatang` VARCHAR(255) NULL,
  `Catatan` TEXT NULL,
  `Versi` INT UNSIGNED NOT NULL DEFAULT 1,
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqAsetKode` (`OrganisasiId`,`KodeAset`),
  KEY `IdxAsetNomorSeri` (`OrganisasiId`,`NomorSeri`),
  KEY `IdxAsetLokasi` (`OrganisasiId`,`LokasiId`,`Status`),
  KEY `IdxAsetKategori` (`OrganisasiId`,`KategoriAsetId`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`UnitOrganisasiId`) REFERENCES `UnitOrganisasi` (`Id`),
  FOREIGN KEY (`LokasiId`) REFERENCES `Lokasi` (`Id`),
  FOREIGN KEY (`KategoriAsetId`) REFERENCES `KategoriAset` (`Id`),
  FOREIGN KEY (`ModelAsetId`) REFERENCES `ModelAset` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `RelasiAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AsetIndukId` CHAR(26) NOT NULL,
  `AsetAnakId` CHAR(26) NOT NULL,
  `JenisRelasi` VARCHAR(60) NOT NULL DEFAULT 'Komponen',
  `Jumlah` DECIMAL(14,4) NOT NULL DEFAULT 1,
  `MulaiPada` DATE NULL,
  `SelesaiPada` DATE NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqRelasiAset` (`AsetIndukId`,`AsetAnakId`,`JenisRelasi`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AsetIndukId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`AsetAnakId`) REFERENCES `Aset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `RiwayatLokasiAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `LokasiAsalId` CHAR(26) NULL,
  `LokasiTujuanId` CHAR(26) NULL,
  `JenisPerpindahan` VARCHAR(60) NOT NULL,
  `ReferensiJenis` VARCHAR(80) NULL,
  `ReferensiId` CHAR(26) NULL,
  `Alasan` TEXT NULL,
  `DipindahkanOleh` CHAR(26) NULL,
  `DipindahkanPada` DATETIME(6) NOT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxRiwayatLokasiAset` (`AsetId`,`DipindahkanPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`LokasiAsalId`) REFERENCES `Lokasi` (`Id`),
  FOREIGN KEY (`LokasiTujuanId`) REFERENCES `Lokasi` (`Id`),
  FOREIGN KEY (`DipindahkanOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `RiwayatPenanggungJawabAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `PenggunaId` CHAR(26) NULL,
  `UnitOrganisasiId` CHAR(26) NULL,
  `MulaiPada` DATETIME(6) NOT NULL,
  `SelesaiPada` DATETIME(6) NULL,
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxPenanggungJawabAset` (`AsetId`,`MulaiPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`),
  FOREIGN KEY (`UnitOrganisasiId`) REFERENCES `UnitOrganisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `GaransiAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `PenyediaId` CHAR(26) NULL,
  `NomorGaransi` VARCHAR(160) NULL,
  `JenisGaransi` VARCHAR(60) NULL,
  `MulaiPada` DATE NOT NULL,
  `BerakhirPada` DATE NOT NULL,
  `Cakupan` TEXT NULL,
  `Status` VARCHAR(30) NOT NULL DEFAULT 'Aktif',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxGaransiAset` (`AsetId`,`BerakhirPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `NilaiAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `TanggalNilai` DATE NOT NULL,
  `NilaiBuku` DECIMAL(20,2) NOT NULL,
  `AkumulasiPenyusutan` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `BebanPenyusutanPeriode` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Metode` VARCHAR(40) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqNilaiAsetTanggal` (`AsetId`,`TanggalNilai`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `MeterAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `Nama` VARCHAR(120) NOT NULL,
  `Satuan` VARCHAR(50) NOT NULL,
  `Jenis` VARCHAR(40) NOT NULL DEFAULT 'Kumulatif',
  `NilaiAwal` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxMeterAset` (`AsetId`,`Aktif`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PembacaanMeterAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `MeterAsetId` CHAR(26) NOT NULL,
  `Nilai` DECIMAL(20,4) NOT NULL,
  `DibacaPada` DATETIME(6) NOT NULL,
  `Sumber` VARCHAR(40) NOT NULL DEFAULT 'Manual',
  `DicatatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxPembacaanMeter` (`MeterAsetId`,`DibacaPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`MeterAsetId`) REFERENCES `MeterAset` (`Id`),
  FOREIGN KEY (`DicatatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 05. MUTASI, SERAH TERIMA, PENGHAPUSAN ASET
-- ============================================================

CREATE TABLE `PermintaanMutasiAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `JenisMutasi` VARCHAR(60) NOT NULL,
  `UnitAsalId` CHAR(26) NULL,
  `UnitTujuanId` CHAR(26) NULL,
  `LokasiAsalId` CHAR(26) NULL,
  `LokasiTujuanId` CHAR(26) NULL,
  `Alasan` TEXT NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `DimintaOleh` CHAR(26) NOT NULL,
  `DimintaPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DisetujuiPada` DATETIME(6) NULL,
  `SelesaiPada` DATETIME(6) NULL,
  `Versi` INT UNSIGNED NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPermintaanMutasiNomor` (`OrganisasiId`,`Nomor`),
  KEY `IdxPermintaanMutasiStatus` (`OrganisasiId`,`Status`,`DimintaPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`UnitAsalId`) REFERENCES `UnitOrganisasi` (`Id`),
  FOREIGN KEY (`UnitTujuanId`) REFERENCES `UnitOrganisasi` (`Id`),
  FOREIGN KEY (`LokasiAsalId`) REFERENCES `Lokasi` (`Id`),
  FOREIGN KEY (`LokasiTujuanId`) REFERENCES `Lokasi` (`Id`),
  FOREIGN KEY (`DimintaOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DetailMutasiAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PermintaanMutasiAsetId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Menunggu',
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqDetailMutasiAset` (`PermintaanMutasiAsetId`,`AsetId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PermintaanMutasiAsetId`) REFERENCES `PermintaanMutasiAset` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `SerahTerimaAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `PermintaanMutasiAsetId` CHAR(26) NULL,
  `Jenis` VARCHAR(60) NOT NULL,
  `PihakMenyerahkan` CHAR(26) NULL,
  `PihakMenerima` CHAR(26) NULL,
  `DiserahkanPada` DATETIME(6) NULL,
  `DiterimaPada` DATETIME(6) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqSerahTerimaNomor` (`OrganisasiId`,`Nomor`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PermintaanMutasiAsetId`) REFERENCES `PermintaanMutasiAset` (`Id`),
  FOREIGN KEY (`PihakMenyerahkan`) REFERENCES `Pengguna` (`Id`),
  FOREIGN KEY (`PihakMenerima`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DetailSerahTerimaAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `SerahTerimaAsetId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `KondisiSaatDiserahkan` VARCHAR(60) NULL,
  `KondisiSaatDiterima` VARCHAR(60) NULL,
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqDetailSerahTerimaAset` (`SerahTerimaAsetId`,`AsetId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`SerahTerimaAsetId`) REFERENCES `SerahTerimaAset` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PengajuanPenghapusanAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `Alasan` TEXT NOT NULL,
  `MetodePenghapusan` VARCHAR(60) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `DiajukanOleh` CHAR(26) NOT NULL,
  `DiajukanPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiselesaikanPada` DATETIME(6) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPengajuanPenghapusanNomor` (`OrganisasiId`,`Nomor`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`DiajukanOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DetailPenghapusanAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PengajuanPenghapusanAsetId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `NilaiBukuSaatPenghapusan` DECIMAL(20,2) NULL,
  `HasilPelepasan` DECIMAL(20,2) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Menunggu',
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqDetailPenghapusanAset` (`PengajuanPenghapusanAsetId`,`AsetId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PengajuanPenghapusanAsetId`) REFERENCES `PengajuanPenghapusanAset` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 06. SLA, KELUHAN, WORK ORDER
-- ============================================================

CREATE TABLE `TingkatLayanan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(60) NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `Deskripsi` TEXT NULL,
  `HariKerja` JSON NULL,
  `JamKerjaMulai` TIME NOT NULL DEFAULT '08:00:00',
  `JamKerjaSelesai` TIME NOT NULL DEFAULT '17:00:00',
  `MemperhitungkanHariLibur` TINYINT(1) NOT NULL DEFAULT 1,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqTingkatLayananKode` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `AturanTingkatLayanan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `TingkatLayananId` CHAR(26) NOT NULL,
  `Prioritas` VARCHAR(40) NOT NULL,
  `MenitRespons` INT UNSIGNED NULL,
  `MenitMulaiPengerjaan` INT UNSIGNED NULL,
  `MenitPenyelesaian` INT UNSIGNED NULL,
  `MenghitungJamKerja` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqAturanTingkatLayananPrioritas` (`TingkatLayananId`,`Prioritas`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`TingkatLayananId`) REFERENCES `TingkatLayanan` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KategoriKeluhan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `IndukId` CHAR(26) NULL,
  `Kode` VARCHAR(60) NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `TingkatLayananId` CHAR(26) NULL,
  `PrioritasBawaan` VARCHAR(40) NOT NULL DEFAULT 'Normal',
  `AsetWajib` TINYINT(1) NOT NULL DEFAULT 0,
  `PeranPenanggungJawabId` CHAR(26) NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKategoriKeluhan` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`IndukId`) REFERENCES `KategoriKeluhan` (`Id`),
  FOREIGN KEY (`TingkatLayananId`) REFERENCES `TingkatLayanan` (`Id`),
  FOREIGN KEY (`PeranPenanggungJawabId`) REFERENCES `Peran` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Keluhan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `KategoriKeluhanId` CHAR(26) NULL,
  `TingkatLayananId` CHAR(26) NULL,
  `AsetId` CHAR(26) NULL,
  `LokasiId` CHAR(26) NULL,
  `Judul` VARCHAR(220) NOT NULL,
  `Deskripsi` TEXT NOT NULL,
  `Prioritas` VARCHAR(40) NOT NULL DEFAULT 'Normal',
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Baru',
  `Sumber` VARCHAR(40) NOT NULL DEFAULT 'Web',
  `PelaporId` CHAR(26) NULL,
  `NamaPelaporEksternal` VARCHAR(180) NULL,
  `KontakPelaporEksternal` VARCHAR(180) NULL,
  `DilaporkanPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiresponsPada` DATETIME(6) NULL,
  `BatasResponsPada` DATETIME(6) NULL,
  `BatasPenyelesaianPada` DATETIME(6) NULL,
  `DiresolusikanPada` DATETIME(6) NULL,
  `DitutupPada` DATETIME(6) NULL,
  `Rating` TINYINT UNSIGNED NULL,
  `Ulasan` TEXT NULL,
  `Versi` INT UNSIGNED NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKeluhanNomor` (`OrganisasiId`,`Nomor`),
  KEY `IdxKeluhanStatus` (`OrganisasiId`,`Status`,`Prioritas`,`DilaporkanPada`),
  KEY `IdxKeluhanAset` (`AsetId`,`Status`),
  KEY `IdxKeluhanBatasRespons` (`OrganisasiId`,`Status`,`BatasResponsPada`),
  KEY `IdxKeluhanBatasPenyelesaian` (`OrganisasiId`,`Status`,`BatasPenyelesaianPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`KategoriKeluhanId`) REFERENCES `KategoriKeluhan` (`Id`),
  FOREIGN KEY (`TingkatLayananId`) REFERENCES `TingkatLayanan` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`LokasiId`) REFERENCES `Lokasi` (`Id`),
  FOREIGN KEY (`PelaporId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `RiwayatStatusKeluhan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `KeluhanId` CHAR(26) NOT NULL,
  `StatusSebelum` VARCHAR(40) NULL,
  `StatusSesudah` VARCHAR(40) NOT NULL,
  `Catatan` TEXT NULL,
  `DiubahOleh` CHAR(26) NULL,
  `DiubahPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxRiwayatStatusKeluhan` (`KeluhanId`,`DiubahPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`KeluhanId`) REFERENCES `Keluhan` (`Id`),
  FOREIGN KEY (`DiubahOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PerintahKerja` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `KeluhanId` CHAR(26) NULL,
  `TingkatLayananId` CHAR(26) NULL,
  `Jenis` VARCHAR(50) NOT NULL,
  `Judul` VARCHAR(220) NOT NULL,
  `Deskripsi` TEXT NULL,
  `Prioritas` VARCHAR(40) NOT NULL DEFAULT 'Normal',
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `LokasiId` CHAR(26) NULL,
  `UnitOrganisasiId` CHAR(26) NULL,
  `DijadwalkanMulaiPada` DATETIME(6) NULL,
  `DijadwalkanSelesaiPada` DATETIME(6) NULL,
  `DiterimaPada` DATETIME(6) NULL,
  `DimulaiPada` DATETIME(6) NULL,
  `DiselesaikanPada` DATETIME(6) NULL,
  `DitutupPada` DATETIME(6) NULL,
  `BatasResponsPada` DATETIME(6) NULL,
  `BatasPenyelesaianPada` DATETIME(6) NULL,
  `PersentaseSelesai` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `MembutuhkanWaktuHenti` TINYINT(1) NOT NULL DEFAULT 0,
  `MembutuhkanPersetujuan` TINYINT(1) NOT NULL DEFAULT 0,
  `RingkasanPenyelesaian` TEXT NULL,
  `DibuatOleh` CHAR(26) NULL,
  `Versi` INT UNSIGNED NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPerintahKerjaNomor` (`OrganisasiId`,`Nomor`),
  KEY `IdxPerintahKerjaStatus` (`OrganisasiId`,`Status`,`Prioritas`,`DijadwalkanMulaiPada`),
  KEY `IdxPerintahKerjaKeluhan` (`KeluhanId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`KeluhanId`) REFERENCES `Keluhan` (`Id`),
  FOREIGN KEY (`TingkatLayananId`) REFERENCES `TingkatLayanan` (`Id`),
  FOREIGN KEY (`LokasiId`) REFERENCES `Lokasi` (`Id`),
  FOREIGN KEY (`UnitOrganisasiId`) REFERENCES `UnitOrganisasi` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PerintahKerjaAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `Utama` TINYINT(1) NOT NULL DEFAULT 0,
  `KondisiAwal` VARCHAR(60) NULL,
  `KondisiAkhir` VARCHAR(60) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPerintahKerjaAset` (`PerintahKerjaId`,`AsetId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PenugasanPerintahKerja` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NOT NULL,
  `PenggunaId` CHAR(26) NOT NULL,
  `PeranTugas` VARCHAR(60) NOT NULL DEFAULT 'Teknisi',
  `DitugaskanOleh` CHAR(26) NULL,
  `DitugaskanPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiterimaPada` DATETIME(6) NULL,
  `SelesaiPada` DATETIME(6) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Ditugaskan',
  PRIMARY KEY (`Id`),
  KEY `IdxPenugasanPerintahKerja` (`PenggunaId`,`Status`,`DitugaskanPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`),
  FOREIGN KEY (`DitugaskanOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `RiwayatStatusPerintahKerja` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NOT NULL,
  `StatusSebelum` VARCHAR(40) NULL,
  `StatusSesudah` VARCHAR(40) NOT NULL,
  `Catatan` TEXT NULL,
  `DiubahOleh` CHAR(26) NULL,
  `DiubahPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxRiwayatStatusPerintahKerja` (`PerintahKerjaId`,`DiubahPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`DiubahOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `WaktuKerja` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NOT NULL,
  `PenggunaId` CHAR(26) NOT NULL,
  `MulaiPada` DATETIME(6) NOT NULL,
  `SelesaiPada` DATETIME(6) NULL,
  `DurasiMenit` INT UNSIGNED NULL,
  `JenisWaktu` VARCHAR(40) NOT NULL DEFAULT 'Kerja',
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxWaktuKerjaPerintah` (`PerintahKerjaId`,`MulaiPada`),
  KEY `IdxWaktuKerjaPengguna` (`PenggunaId`,`MulaiPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `WaktuHentiAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NULL,
  `MulaiPada` DATETIME(6) NOT NULL,
  `SelesaiPada` DATETIME(6) NULL,
  `DurasiMenit` INT UNSIGNED NULL,
  `Jenis` VARCHAR(50) NOT NULL DEFAULT 'TidakTerencana',
  `Alasan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxWaktuHentiAset` (`AsetId`,`MulaiPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `BiayaPerintahKerja` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NOT NULL,
  `JenisBiaya` VARCHAR(60) NOT NULL,
  `Deskripsi` VARCHAR(255) NULL,
  `Jumlah` DECIMAL(20,2) NOT NULL,
  `MataUang` CHAR(3) NOT NULL DEFAULT 'IDR',
  `PenyediaId` CHAR(26) NULL,
  `TanggalBiaya` DATE NOT NULL,
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxBiayaPerintahKerja` (`PerintahKerjaId`,`JenisBiaya`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KodeKegagalan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `KategoriAsetId` CHAR(26) NULL,
  `Kode` VARCHAR(60) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Jenis` VARCHAR(60) NOT NULL,
  `Keterangan` TEXT NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKodeKegagalan` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`KategoriAsetId`) REFERENCES `KategoriAset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `AnalisisKegagalan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NOT NULL,
  `KodeMasalahId` CHAR(26) NULL,
  `KodePenyebabId` CHAR(26) NULL,
  `KodeTindakanId` CHAR(26) NULL,
  `AkarMasalah` TEXT NULL,
  `TindakanKorektif` TEXT NULL,
  `TindakanPencegahan` TEXT NULL,
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqAnalisisKegagalanPerintah` (`PerintahKerjaId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`KodeMasalahId`) REFERENCES `KodeKegagalan` (`Id`),
  FOREIGN KEY (`KodePenyebabId`) REFERENCES `KodeKegagalan` (`Id`),
  FOREIGN KEY (`KodeTindakanId`) REFERENCES `KodeKegagalan` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 07. CHECKLIST, PREVENTIVE, INSPEKSI
-- ============================================================

CREATE TABLE `TemplatDaftarPeriksa` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Jenis` VARCHAR(60) NOT NULL,
  `KategoriAsetId` CHAR(26) NULL,
  `ModelAsetId` CHAR(26) NULL,
  `VersiTemplat` INT UNSIGNED NOT NULL DEFAULT 1,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqTemplatDaftarPeriksa` (`OrganisasiId`,`Kode`,`VersiTemplat`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`KategoriAsetId`) REFERENCES `KategoriAset` (`Id`),
  FOREIGN KEY (`ModelAsetId`) REFERENCES `ModelAset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `ButirTemplatDaftarPeriksa` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `TemplatDaftarPeriksaId` CHAR(26) NOT NULL,
  `Urutan` INT NOT NULL DEFAULT 0,
  `Kode` VARCHAR(80) NULL,
  `Pertanyaan` TEXT NOT NULL,
  `TipeJawaban` VARCHAR(50) NOT NULL,
  `Satuan` VARCHAR(50) NULL,
  `Wajib` TINYINT(1) NOT NULL DEFAULT 0,
  `NilaiMinimum` DECIMAL(20,6) NULL,
  `NilaiMaksimum` DECIMAL(20,6) NULL,
  `Pilihan` JSON NULL,
  `BuktiFotoWajib` TINYINT(1) NOT NULL DEFAULT 0,
  `MemicuTemuanJika` JSON NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxButirTemplatDaftarPeriksa` (`TemplatDaftarPeriksaId`,`Urutan`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`TemplatDaftarPeriksaId`) REFERENCES `TemplatDaftarPeriksa` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PelaksanaanDaftarPeriksa` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `TemplatDaftarPeriksaId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NULL,
  `AsetId` CHAR(26) NULL,
  `DilaksanakanOleh` CHAR(26) NULL,
  `MulaiPada` DATETIME(6) NULL,
  `SelesaiPada` DATETIME(6) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `Skor` DECIMAL(8,2) NULL,
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxPelaksanaanDaftarPeriksa` (`PerintahKerjaId`,`AsetId`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`TemplatDaftarPeriksaId`) REFERENCES `TemplatDaftarPeriksa` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`DilaksanakanOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `JawabanDaftarPeriksa` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PelaksanaanDaftarPeriksaId` CHAR(26) NOT NULL,
  `ButirTemplatDaftarPeriksaId` CHAR(26) NOT NULL,
  `NilaiTeks` TEXT NULL,
  `NilaiAngka` DECIMAL(20,6) NULL,
  `NilaiBoolean` TINYINT(1) NULL,
  `NilaiTanggal` DATETIME(6) NULL,
  `NilaiJson` JSON NULL,
  `Sesuai` TINYINT(1) NULL,
  `Catatan` TEXT NULL,
  `DijawabPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqJawabanDaftarPeriksa` (`PelaksanaanDaftarPeriksaId`,`ButirTemplatDaftarPeriksaId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PelaksanaanDaftarPeriksaId`) REFERENCES `PelaksanaanDaftarPeriksa` (`Id`),
  FOREIGN KEY (`ButirTemplatDaftarPeriksaId`) REFERENCES `ButirTemplatDaftarPeriksa` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `RencanaPemeliharaan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(200) NOT NULL,
  `Jenis` VARCHAR(50) NOT NULL DEFAULT 'Preventif',
  `TemplatDaftarPeriksaId` CHAR(26) NULL,
  `Prioritas` VARCHAR(40) NOT NULL DEFAULT 'Normal',
  `StrategiJadwal` VARCHAR(50) NOT NULL DEFAULT 'Kalender',
  `IntervalNilai` INT UNSIGNED NULL,
  `IntervalSatuan` VARCHAR(30) NULL,
  `BerdasarkanMeter` TINYINT(1) NOT NULL DEFAULT 0,
  `AmbangMeter` DECIMAL(20,4) NULL,
  `ToleransiHari` INT UNSIGNED NOT NULL DEFAULT 0,
  `BuatPerintahKerjaHariSebelum` INT UNSIGNED NOT NULL DEFAULT 7,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqRencanaPemeliharaan` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`TemplatDaftarPeriksaId`) REFERENCES `TemplatDaftarPeriksa` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `RencanaPemeliharaanAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `RencanaPemeliharaanId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `TanggalMulai` DATE NOT NULL,
  `TanggalBerikutnya` DATE NULL,
  `NilaiMeterBerikutnya` DECIMAL(20,4) NULL,
  `TerakhirDilaksanakanPada` DATETIME(6) NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqRencanaPemeliharaanAset` (`RencanaPemeliharaanId`,`AsetId`),
  KEY `IdxRencanaPemeliharaanAsetTanggal` (`OrganisasiId`,`TanggalBerikutnya`,`Aktif`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`RencanaPemeliharaanId`) REFERENCES `RencanaPemeliharaan` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `JadwalPemeliharaan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `RencanaPemeliharaanAsetId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NULL,
  `TanggalJadwal` DATE NOT NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Terjadwal',
  `DihasilkanOtomatis` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqJadwalPemeliharaan` (`RencanaPemeliharaanAsetId`,`TanggalJadwal`),
  KEY `IdxJadwalPemeliharaanTanggal` (`OrganisasiId`,`TanggalJadwal`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`RencanaPemeliharaanAsetId`) REFERENCES `RencanaPemeliharaanAset` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `TemplatInspeksi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `KategoriAsetId` CHAR(26) NULL,
  `TemplatDaftarPeriksaId` CHAR(26) NOT NULL,
  `IntervalHari` INT UNSIGNED NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqTemplatInspeksi` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`KategoriAsetId`) REFERENCES `KategoriAset` (`Id`),
  FOREIGN KEY (`TemplatDaftarPeriksaId`) REFERENCES `TemplatDaftarPeriksa` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Inspeksi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `TemplatInspeksiId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `PelaksanaanDaftarPeriksaId` CHAR(26) NULL,
  `DijadwalkanPada` DATETIME(6) NULL,
  `DilaksanakanPada` DATETIME(6) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Terjadwal',
  `Hasil` VARCHAR(40) NULL,
  `Temuan` TEXT NULL,
  `TindakLanjut` TEXT NULL,
  `PerintahKerjaId` CHAR(26) NULL,
  `DilaksanakanOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqInspeksiNomor` (`OrganisasiId`,`Nomor`),
  KEY `IdxInspeksiAset` (`AsetId`,`DijadwalkanPada`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`TemplatInspeksiId`) REFERENCES `TemplatInspeksi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`PelaksanaanDaftarPeriksaId`) REFERENCES `PelaksanaanDaftarPeriksa` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`DilaksanakanOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 08. KALIBRASI
-- ============================================================

CREATE TABLE `JenisKalibrasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(60) NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `Deskripsi` TEXT NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqJenisKalibrasi` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `RencanaKalibrasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `JenisKalibrasiId` CHAR(26) NULL,
  `PenyediaId` CHAR(26) NULL,
  `IntervalHari` INT UNSIGNED NOT NULL,
  `TanggalMulai` DATE NOT NULL,
  `TanggalBerikutnya` DATE NOT NULL,
  `PeringatanHariSebelum` INT UNSIGNED NOT NULL DEFAULT 30,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxRencanaKalibrasiTanggal` (`OrganisasiId`,`TanggalBerikutnya`,`Aktif`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`JenisKalibrasiId`) REFERENCES `JenisKalibrasi` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PelaksanaanKalibrasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `RencanaKalibrasiId` CHAR(26) NULL,
  `AsetId` CHAR(26) NOT NULL,
  `JenisKalibrasiId` CHAR(26) NULL,
  `PenyediaId` CHAR(26) NULL,
  `PerintahKerjaId` CHAR(26) NULL,
  `TanggalKalibrasi` DATE NOT NULL,
  `TanggalBerlakuSampai` DATE NULL,
  `Hasil` VARCHAR(40) NOT NULL,
  `NomorSertifikat` VARCHAR(180) NULL,
  `Laboratorium` VARCHAR(200) NULL,
  `KondisiLingkungan` JSON NULL,
  `Catatan` TEXT NULL,
  `DilaksanakanOleh` CHAR(26) NULL,
  `DiverifikasiOleh` CHAR(26) NULL,
  `DiverifikasiPada` DATETIME(6) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPelaksanaanKalibrasiNomor` (`OrganisasiId`,`Nomor`),
  KEY `IdxPelaksanaanKalibrasiAset` (`AsetId`,`TanggalKalibrasi`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`RencanaKalibrasiId`) REFERENCES `RencanaKalibrasi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`JenisKalibrasiId`) REFERENCES `JenisKalibrasi` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`DilaksanakanOleh`) REFERENCES `Pengguna` (`Id`),
  FOREIGN KEY (`DiverifikasiOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `TitikUkurKalibrasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `JenisKalibrasiId` CHAR(26) NULL,
  `KategoriAsetId` CHAR(26) NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `Satuan` VARCHAR(50) NULL,
  `NilaiReferensi` DECIMAL(20,8) NULL,
  `ToleransiMinus` DECIMAL(20,8) NULL,
  `ToleransiPlus` DECIMAL(20,8) NULL,
  `Urutan` INT NOT NULL DEFAULT 0,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`JenisKalibrasiId`) REFERENCES `JenisKalibrasi` (`Id`),
  FOREIGN KEY (`KategoriAsetId`) REFERENCES `KategoriAset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `HasilTitikUkurKalibrasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PelaksanaanKalibrasiId` CHAR(26) NOT NULL,
  `TitikUkurKalibrasiId` CHAR(26) NULL,
  `NamaTitik` VARCHAR(160) NULL,
  `NilaiReferensi` DECIMAL(20,8) NULL,
  `NilaiTerukur` DECIMAL(20,8) NULL,
  `Koreksi` DECIMAL(20,8) NULL,
  `Ketidakpastian` DECIMAL(20,8) NULL,
  `Satuan` VARCHAR(50) NULL,
  `Hasil` VARCHAR(40) NULL,
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxHasilKalibrasi` (`PelaksanaanKalibrasiId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PelaksanaanKalibrasiId`) REFERENCES `PelaksanaanKalibrasi` (`Id`),
  FOREIGN KEY (`TitikUkurKalibrasiId`) REFERENCES `TitikUkurKalibrasi` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 09. GUDANG, SUKU CADANG, STOK
-- ============================================================

CREATE TABLE `Gudang` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `LokasiId` CHAR(26) NULL,
  `Kode` VARCHAR(60) NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `PenanggungJawabId` CHAR(26) NULL,
  `Status` VARCHAR(30) NOT NULL DEFAULT 'Aktif',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqGudangKode` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`LokasiId`) REFERENCES `Lokasi` (`Id`),
  FOREIGN KEY (`PenanggungJawabId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `LokasiGudang` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `GudangId` CHAR(26) NOT NULL,
  `IndukId` CHAR(26) NULL,
  `Kode` VARCHAR(60) NOT NULL,
  `Nama` VARCHAR(120) NOT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqLokasiGudang` (`GudangId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`GudangId`) REFERENCES `Gudang` (`Id`),
  FOREIGN KEY (`IndukId`) REFERENCES `LokasiGudang` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KategoriSukuCadang` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `IndukId` CHAR(26) NULL,
  `Kode` VARCHAR(60) NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKategoriSukuCadang` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`IndukId`) REFERENCES `KategoriSukuCadang` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `SukuCadang` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `KategoriSukuCadangId` CHAR(26) NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(200) NOT NULL,
  `NomorBagian` VARCHAR(160) NULL,
  `KodeBatang` VARCHAR(255) NULL,
  `SatuanDasar` VARCHAR(50) NOT NULL,
  `StokMinimum` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `StokMaksimum` DECIMAL(20,4) NULL,
  `TitikPesanUlang` DECIMAL(20,4) NULL,
  `HargaRataRata` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `MemakaiBatch` TINYINT(1) NOT NULL DEFAULT 0,
  `MemakaiKadaluarsa` TINYINT(1) NOT NULL DEFAULT 0,
  `Status` VARCHAR(30) NOT NULL DEFAULT 'Aktif',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `DihapusPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqSukuCadangKode` (`OrganisasiId`,`Kode`),
  KEY `IdxSukuCadangNomorBagian` (`OrganisasiId`,`NomorBagian`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`KategoriSukuCadangId`) REFERENCES `KategoriSukuCadang` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KompatibilitasSukuCadang` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `SukuCadangId` CHAR(26) NOT NULL,
  `KategoriAsetId` CHAR(26) NULL,
  `ModelAsetId` CHAR(26) NULL,
  `AsetId` CHAR(26) NULL,
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxKompatibilitasSukuCadang` (`SukuCadangId`,`ModelAsetId`,`AsetId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`SukuCadangId`) REFERENCES `SukuCadang` (`Id`),
  FOREIGN KEY (`KategoriAsetId`) REFERENCES `KategoriAset` (`Id`),
  FOREIGN KEY (`ModelAsetId`) REFERENCES `ModelAset` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KelompokSukuCadang` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `SukuCadangId` CHAR(26) NOT NULL,
  `NomorBatch` VARCHAR(120) NOT NULL,
  `TanggalProduksi` DATE NULL,
  `TanggalKadaluarsa` DATE NULL,
  `HargaPerolehan` DECIMAL(20,2) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKelompokSukuCadang` (`SukuCadangId`,`NomorBatch`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`SukuCadangId`) REFERENCES `SukuCadang` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `StokSukuCadang` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `GudangId` CHAR(26) NOT NULL,
  `LokasiGudangId` CHAR(26) NULL,
  `SukuCadangId` CHAR(26) NOT NULL,
  `KelompokSukuCadangId` CHAR(26) NULL,
  `JumlahTersedia` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `JumlahDipesan` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `JumlahDitahan` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `Versi` INT UNSIGNED NOT NULL DEFAULT 1,
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqStokSukuCadang` (`GudangId`,`LokasiGudangId`,`SukuCadangId`,`KelompokSukuCadangId`),
  KEY `IdxStokSukuCadang` (`OrganisasiId`,`SukuCadangId`,`GudangId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`GudangId`) REFERENCES `Gudang` (`Id`),
  FOREIGN KEY (`LokasiGudangId`) REFERENCES `LokasiGudang` (`Id`),
  FOREIGN KEY (`SukuCadangId`) REFERENCES `SukuCadang` (`Id`),
  FOREIGN KEY (`KelompokSukuCadangId`) REFERENCES `KelompokSukuCadang` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `MutasiStok` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `Jenis` VARCHAR(60) NOT NULL,
  `GudangAsalId` CHAR(26) NULL,
  `GudangTujuanId` CHAR(26) NULL,
  `ReferensiJenis` VARCHAR(80) NULL,
  `ReferensiId` CHAR(26) NULL,
  `Tanggal` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `Catatan` TEXT NULL,
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqMutasiStokNomor` (`OrganisasiId`,`Nomor`),
  KEY `IdxMutasiStokTanggal` (`OrganisasiId`,`Tanggal`,`Jenis`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`GudangAsalId`) REFERENCES `Gudang` (`Id`),
  FOREIGN KEY (`GudangTujuanId`) REFERENCES `Gudang` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DetailMutasiStok` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `MutasiStokId` CHAR(26) NOT NULL,
  `SukuCadangId` CHAR(26) NOT NULL,
  `KelompokSukuCadangId` CHAR(26) NULL,
  `Jumlah` DECIMAL(20,4) NOT NULL,
  `HargaSatuan` DECIMAL(20,2) NULL,
  `LokasiGudangAsalId` CHAR(26) NULL,
  `LokasiGudangTujuanId` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxDetailMutasiStok` (`MutasiStokId`,`SukuCadangId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`MutasiStokId`) REFERENCES `MutasiStok` (`Id`),
  FOREIGN KEY (`SukuCadangId`) REFERENCES `SukuCadang` (`Id`),
  FOREIGN KEY (`KelompokSukuCadangId`) REFERENCES `KelompokSukuCadang` (`Id`),
  FOREIGN KEY (`LokasiGudangAsalId`) REFERENCES `LokasiGudang` (`Id`),
  FOREIGN KEY (`LokasiGudangTujuanId`) REFERENCES `LokasiGudang` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PemakaianSukuCadang` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NOT NULL,
  `SukuCadangId` CHAR(26) NOT NULL,
  `GudangId` CHAR(26) NULL,
  `KelompokSukuCadangId` CHAR(26) NULL,
  `Jumlah` DECIMAL(20,4) NOT NULL,
  `HargaSatuan` DECIMAL(20,2) NULL,
  `MutasiStokId` CHAR(26) NULL,
  `DipakaiOleh` CHAR(26) NULL,
  `DipakaiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxPemakaianSukuCadang` (`PerintahKerjaId`,`SukuCadangId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`SukuCadangId`) REFERENCES `SukuCadang` (`Id`),
  FOREIGN KEY (`GudangId`) REFERENCES `Gudang` (`Id`),
  FOREIGN KEY (`KelompokSukuCadangId`) REFERENCES `KelompokSukuCadang` (`Id`),
  FOREIGN KEY (`MutasiStokId`) REFERENCES `MutasiStok` (`Id`),
  FOREIGN KEY (`DipakaiOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `ReservasiSukuCadang` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PerintahKerjaId` CHAR(26) NULL,
  `GudangId` CHAR(26) NOT NULL,
  `SukuCadangId` CHAR(26) NOT NULL,
  `Jumlah` DECIMAL(20,4) NOT NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Aktif',
  `KadaluarsaPada` DATETIME(6) NULL,
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxReservasiSukuCadang` (`SukuCadangId`,`GudangId`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PerintahKerjaId`) REFERENCES `PerintahKerja` (`Id`),
  FOREIGN KEY (`GudangId`) REFERENCES `Gudang` (`Id`),
  FOREIGN KEY (`SukuCadangId`) REFERENCES `SukuCadang` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 10. PERENCANAAN, ANGGARAN, PENGADAAN
-- ============================================================

CREATE TABLE `Anggaran` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `UnitOrganisasiId` CHAR(26) NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Tahun` SMALLINT UNSIGNED NOT NULL,
  `MataUang` CHAR(3) NOT NULL DEFAULT 'IDR',
  `Jumlah` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqAnggaran` (`OrganisasiId`,`Kode`,`Tahun`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`UnitOrganisasiId`) REFERENCES `UnitOrganisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PosAnggaran` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AnggaranId` CHAR(26) NOT NULL,
  `IndukId` CHAR(26) NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Jumlah` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Terpakai` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Ditahan` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPosAnggaran` (`AnggaranId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AnggaranId`) REFERENCES `Anggaran` (`Id`),
  FOREIGN KEY (`IndukId`) REFERENCES `PosAnggaran` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `TransaksiAnggaran` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PosAnggaranId` CHAR(26) NOT NULL,
  `Jenis` VARCHAR(50) NOT NULL,
  `ReferensiJenis` VARCHAR(80) NULL,
  `ReferensiId` CHAR(26) NULL,
  `Jumlah` DECIMAL(20,2) NOT NULL,
  `Tanggal` DATE NOT NULL,
  `Keterangan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxTransaksiAnggaran` (`PosAnggaranId`,`Tanggal`,`Jenis`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PosAnggaranId`) REFERENCES `PosAnggaran` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `UsulanAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `UnitOrganisasiId` CHAR(26) NOT NULL,
  `KategoriAsetId` CHAR(26) NULL,
  `ModelAsetId` CHAR(26) NULL,
  `NamaKebutuhan` VARCHAR(220) NOT NULL,
  `Jumlah` DECIMAL(14,4) NOT NULL DEFAULT 1,
  `EstimasiHargaSatuan` DECIMAL(20,2) NULL,
  `Alasan` TEXT NOT NULL,
  `JenisKebutuhan` VARCHAR(60) NULL,
  `TahunKebutuhan` SMALLINT UNSIGNED NULL,
  `Prioritas` VARCHAR(40) NOT NULL DEFAULT 'Normal',
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `DiajukanOleh` CHAR(26) NOT NULL,
  `DiajukanPada` DATETIME(6) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqUsulanAsetNomor` (`OrganisasiId`,`Nomor`),
  KEY `IdxUsulanAsetStatus` (`OrganisasiId`,`Status`,`TahunKebutuhan`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`UnitOrganisasiId`) REFERENCES `UnitOrganisasi` (`Id`),
  FOREIGN KEY (`KategoriAsetId`) REFERENCES `KategoriAset` (`Id`),
  FOREIGN KEY (`ModelAsetId`) REFERENCES `ModelAset` (`Id`),
  FOREIGN KEY (`DiajukanOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PenilaianUsulanAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `UsulanAsetId` CHAR(26) NOT NULL,
  `Kriteria` VARCHAR(160) NOT NULL,
  `Bobot` DECIMAL(8,4) NOT NULL DEFAULT 1,
  `Nilai` DECIMAL(8,4) NOT NULL,
  `Skor` DECIMAL(12,4) NOT NULL,
  `DinilaiOleh` CHAR(26) NULL,
  `DinilaiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxPenilaianUsulanAset` (`UsulanAsetId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`UsulanAsetId`) REFERENCES `UsulanAset` (`Id`),
  FOREIGN KEY (`DinilaiOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `RencanaPengadaan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `Nama` VARCHAR(200) NOT NULL,
  `Tahun` SMALLINT UNSIGNED NOT NULL,
  `PosAnggaranId` CHAR(26) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `TotalEstimasi` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqRencanaPengadaanNomor` (`OrganisasiId`,`Nomor`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PosAnggaranId`) REFERENCES `PosAnggaran` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DetailRencanaPengadaan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `RencanaPengadaanId` CHAR(26) NOT NULL,
  `UsulanAsetId` CHAR(26) NULL,
  `SukuCadangId` CHAR(26) NULL,
  `Deskripsi` VARCHAR(255) NOT NULL,
  `Jumlah` DECIMAL(20,4) NOT NULL,
  `Satuan` VARCHAR(50) NOT NULL,
  `HargaEstimasi` DECIMAL(20,2) NULL,
  `BulanRencana` TINYINT UNSIGNED NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxDetailRencanaPengadaan` (`RencanaPengadaanId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`RencanaPengadaanId`) REFERENCES `RencanaPengadaan` (`Id`),
  FOREIGN KEY (`UsulanAsetId`) REFERENCES `UsulanAset` (`Id`),
  FOREIGN KEY (`SukuCadangId`) REFERENCES `SukuCadang` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PermintaanPembelian` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `UnitOrganisasiId` CHAR(26) NULL,
  `RencanaPengadaanId` CHAR(26) NULL,
  `PosAnggaranId` CHAR(26) NULL,
  `TanggalPermintaan` DATE NOT NULL,
  `TanggalDibutuhkan` DATE NULL,
  `Prioritas` VARCHAR(40) NOT NULL DEFAULT 'Normal',
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `Alasan` TEXT NULL,
  `DimintaOleh` CHAR(26) NOT NULL,
  `TotalEstimasi` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPermintaanPembelianNomor` (`OrganisasiId`,`Nomor`),
  KEY `IdxPermintaanPembelianStatus` (`OrganisasiId`,`Status`,`TanggalPermintaan`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`UnitOrganisasiId`) REFERENCES `UnitOrganisasi` (`Id`),
  FOREIGN KEY (`RencanaPengadaanId`) REFERENCES `RencanaPengadaan` (`Id`),
  FOREIGN KEY (`PosAnggaranId`) REFERENCES `PosAnggaran` (`Id`),
  FOREIGN KEY (`DimintaOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DetailPermintaanPembelian` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PermintaanPembelianId` CHAR(26) NOT NULL,
  `JenisItem` VARCHAR(40) NOT NULL,
  `AsetReferensiId` CHAR(26) NULL,
  `SukuCadangId` CHAR(26) NULL,
  `Deskripsi` VARCHAR(255) NOT NULL,
  `Jumlah` DECIMAL(20,4) NOT NULL,
  `Satuan` VARCHAR(50) NOT NULL,
  `HargaEstimasi` DECIMAL(20,2) NULL,
  `Spesifikasi` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxDetailPermintaanPembelian` (`PermintaanPembelianId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PermintaanPembelianId`) REFERENCES `PermintaanPembelian` (`Id`),
  FOREIGN KEY (`AsetReferensiId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`SukuCadangId`) REFERENCES `SukuCadang` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PermintaanPenawaran` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `PermintaanPembelianId` CHAR(26) NULL,
  `TanggalDibuka` DATETIME(6) NOT NULL,
  `BatasPenawaran` DATETIME(6) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `Catatan` TEXT NULL,
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPermintaanPenawaranNomor` (`OrganisasiId`,`Nomor`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PermintaanPembelianId`) REFERENCES `PermintaanPembelian` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PenyediaPermintaanPenawaran` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PermintaanPenawaranId` CHAR(26) NOT NULL,
  `PenyediaId` CHAR(26) NOT NULL,
  `DikirimPada` DATETIME(6) NULL,
  `DilihatPada` DATETIME(6) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Diundang',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPenyediaPermintaanPenawaran` (`PermintaanPenawaranId`,`PenyediaId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PermintaanPenawaranId`) REFERENCES `PermintaanPenawaran` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PenawaranPenyedia` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PermintaanPenawaranId` CHAR(26) NOT NULL,
  `PenyediaId` CHAR(26) NOT NULL,
  `NomorPenawaran` VARCHAR(120) NULL,
  `TanggalPenawaran` DATE NOT NULL,
  `BerlakuSampai` DATE NULL,
  `MataUang` CHAR(3) NOT NULL DEFAULT 'IDR',
  `Subtotal` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Pajak` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Diskon` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Total` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Diajukan',
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxPenawaranPenyedia` (`PermintaanPenawaranId`,`PenyediaId`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PermintaanPenawaranId`) REFERENCES `PermintaanPenawaran` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DetailPenawaranPenyedia` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PenawaranPenyediaId` CHAR(26) NOT NULL,
  `DetailPermintaanPembelianId` CHAR(26) NULL,
  `Deskripsi` VARCHAR(255) NOT NULL,
  `Jumlah` DECIMAL(20,4) NOT NULL,
  `HargaSatuan` DECIMAL(20,2) NOT NULL,
  `Diskon` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Pajak` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Total` DECIMAL(20,2) NOT NULL,
  `WaktuPengirimanHari` INT UNSIGNED NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenawaranPenyediaId`) REFERENCES `PenawaranPenyedia` (`Id`),
  FOREIGN KEY (`DetailPermintaanPembelianId`) REFERENCES `DetailPermintaanPembelian` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PesananPembelian` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `PenyediaId` CHAR(26) NOT NULL,
  `PermintaanPembelianId` CHAR(26) NULL,
  `PenawaranPenyediaId` CHAR(26) NULL,
  `PosAnggaranId` CHAR(26) NULL,
  `TanggalPesanan` DATE NOT NULL,
  `TanggalKirimRencana` DATE NULL,
  `MataUang` CHAR(3) NOT NULL DEFAULT 'IDR',
  `Subtotal` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Pajak` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Diskon` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Total` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Draft',
  `Catatan` TEXT NULL,
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPesananPembelianNomor` (`OrganisasiId`,`Nomor`),
  KEY `IdxPesananPembelianStatus` (`OrganisasiId`,`Status`,`TanggalPesanan`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`),
  FOREIGN KEY (`PermintaanPembelianId`) REFERENCES `PermintaanPembelian` (`Id`),
  FOREIGN KEY (`PenawaranPenyediaId`) REFERENCES `PenawaranPenyedia` (`Id`),
  FOREIGN KEY (`PosAnggaranId`) REFERENCES `PosAnggaran` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DetailPesananPembelian` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PesananPembelianId` CHAR(26) NOT NULL,
  `JenisItem` VARCHAR(40) NOT NULL,
  `SukuCadangId` CHAR(26) NULL,
  `Deskripsi` VARCHAR(255) NOT NULL,
  `Jumlah` DECIMAL(20,4) NOT NULL,
  `Satuan` VARCHAR(50) NOT NULL,
  `HargaSatuan` DECIMAL(20,2) NOT NULL,
  `Diskon` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Pajak` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Total` DECIMAL(20,2) NOT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxDetailPesananPembelian` (`PesananPembelianId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PesananPembelianId`) REFERENCES `PesananPembelian` (`Id`),
  FOREIGN KEY (`SukuCadangId`) REFERENCES `SukuCadang` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PenerimaanPembelian` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(100) NOT NULL,
  `PesananPembelianId` CHAR(26) NOT NULL,
  `GudangId` CHAR(26) NULL,
  `TanggalTerima` DATETIME(6) NOT NULL,
  `NomorSuratJalan` VARCHAR(120) NULL,
  `DiterimaOleh` CHAR(26) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Diterima',
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPenerimaanPembelianNomor` (`OrganisasiId`,`Nomor`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PesananPembelianId`) REFERENCES `PesananPembelian` (`Id`),
  FOREIGN KEY (`GudangId`) REFERENCES `Gudang` (`Id`),
  FOREIGN KEY (`DiterimaOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DetailPenerimaanPembelian` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PenerimaanPembelianId` CHAR(26) NOT NULL,
  `DetailPesananPembelianId` CHAR(26) NULL,
  `SukuCadangId` CHAR(26) NULL,
  `JumlahDipesan` DECIMAL(20,4) NOT NULL,
  `JumlahDiterima` DECIMAL(20,4) NOT NULL,
  `JumlahDitolak` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `Kondisi` VARCHAR(40) NULL,
  `NomorSeriJson` JSON NULL,
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenerimaanPembelianId`) REFERENCES `PenerimaanPembelian` (`Id`),
  FOREIGN KEY (`DetailPesananPembelianId`) REFERENCES `DetailPesananPembelian` (`Id`),
  FOREIGN KEY (`SukuCadangId`) REFERENCES `SukuCadang` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `TagihanPenyedia` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PenyediaId` CHAR(26) NOT NULL,
  `PesananPembelianId` CHAR(26) NULL,
  `NomorTagihan` VARCHAR(120) NOT NULL,
  `TanggalTagihan` DATE NOT NULL,
  `JatuhTempo` DATE NULL,
  `Subtotal` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Pajak` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Total` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Sisa` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'BelumDibayar',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqTagihanPenyedia` (`OrganisasiId`,`PenyediaId`,`NomorTagihan`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`),
  FOREIGN KEY (`PesananPembelianId`) REFERENCES `PesananPembelian` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PembayaranPenyedia` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `TagihanPenyediaId` CHAR(26) NOT NULL,
  `NomorPembayaran` VARCHAR(120) NOT NULL,
  `TanggalBayar` DATE NOT NULL,
  `Jumlah` DECIMAL(20,2) NOT NULL,
  `Metode` VARCHAR(60) NULL,
  `Referensi` VARCHAR(180) NULL,
  `DibuatOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPembayaranPenyedia` (`OrganisasiId`,`NomorPembayaran`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`TagihanPenyediaId`) REFERENCES `TagihanPenyedia` (`Id`),
  FOREIGN KEY (`DibuatOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 11. KONTRAK & LAYANAN VENDOR
-- ============================================================

CREATE TABLE `Kontrak` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PenyediaId` CHAR(26) NULL,
  `Nomor` VARCHAR(120) NOT NULL,
  `Nama` VARCHAR(220) NOT NULL,
  `Jenis` VARCHAR(60) NOT NULL,
  `MulaiPada` DATE NOT NULL,
  `BerakhirPada` DATE NOT NULL,
  `Nilai` DECIMAL(20,2) NULL,
  `MataUang` CHAR(3) NOT NULL DEFAULT 'IDR',
  `TingkatLayananId` CHAR(26) NULL,
  `PeringatanHariSebelum` INT UNSIGNED NOT NULL DEFAULT 30,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Aktif',
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKontrakNomor` (`OrganisasiId`,`Nomor`),
  KEY `IdxKontrakBerakhir` (`OrganisasiId`,`BerakhirPada`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenyediaId`) REFERENCES `Penyedia` (`Id`),
  FOREIGN KEY (`TingkatLayananId`) REFERENCES `TingkatLayanan` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KontrakAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `KontrakId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `MulaiPada` DATE NULL,
  `BerakhirPada` DATE NULL,
  `Catatan` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKontrakAset` (`KontrakId`,`AsetId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`KontrakId`) REFERENCES `Kontrak` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `LayananKontrak` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `KontrakId` CHAR(26) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Deskripsi` TEXT NULL,
  `Kuota` DECIMAL(20,4) NULL,
  `Satuan` VARCHAR(50) NULL,
  `Terpakai` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`KontrakId`) REFERENCES `Kontrak` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 12. KEPATUHAN, SERTIFIKASI, STANDAR EKSTERNAL
-- ============================================================

CREATE TABLE `StandarKepatuhan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(220) NOT NULL,
  `Penerbit` VARCHAR(180) NULL,
  `VersiStandar` VARCHAR(80) NULL,
  `JenisIndustri` VARCHAR(120) NULL,
  `Deskripsi` TEXT NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxStandarKepatuhan` (`Kode`,`Aktif`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PersyaratanKepatuhan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NULL,
  `StandarKepatuhanId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(100) NOT NULL,
  `Nama` VARCHAR(220) NOT NULL,
  `Deskripsi` TEXT NULL,
  `BuktiYangDiperlukan` TEXT NULL,
  `IntervalHari` INT UNSIGNED NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPersyaratanKepatuhan` (`StandarKepatuhanId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`StandarKepatuhanId`) REFERENCES `StandarKepatuhan` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KepatuhanAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `PersyaratanKepatuhanId` CHAR(26) NOT NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'BelumDiperiksa',
  `TanggalPemeriksaan` DATE NULL,
  `BerlakuSampai` DATE NULL,
  `Catatan` TEXT NULL,
  `DiperiksaOleh` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKepatuhanAset` (`AsetId`,`PersyaratanKepatuhanId`),
  KEY `IdxKepatuhanAsetStatus` (`OrganisasiId`,`Status`,`BerlakuSampai`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`PersyaratanKepatuhanId`) REFERENCES `PersyaratanKepatuhan` (`Id`),
  FOREIGN KEY (`DiperiksaOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `SertifikasiAset` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AsetId` CHAR(26) NOT NULL,
  `JenisSertifikasi` VARCHAR(120) NOT NULL,
  `NomorSertifikat` VARCHAR(180) NULL,
  `Penerbit` VARCHAR(180) NULL,
  `TerbitPada` DATE NULL,
  `BerlakuSampai` DATE NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Aktif',
  `BerkasId` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxSertifikasiAset` (`AsetId`,`BerlakuSampai`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AsetId`) REFERENCES `Aset` (`Id`),
  FOREIGN KEY (`BerkasId`) REFERENCES `Berkas` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `IntegrasiEksternal` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Jenis` VARCHAR(80) NOT NULL,
  `UrlDasar` TEXT NULL,
  `MetodeAutentikasi` VARCHAR(60) NULL,
  `KonfigurasiTerenkripsi` JSON NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Aktif',
  `TerakhirSinkronPada` DATETIME(6) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqIntegrasiEksternal` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PemetaanDataEksternal` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `IntegrasiEksternalId` CHAR(26) NOT NULL,
  `JenisEntitas` VARCHAR(80) NOT NULL,
  `EntitasId` CHAR(26) NOT NULL,
  `KodeEksternal` VARCHAR(255) NOT NULL,
  `DataTambahan` JSON NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPemetaanDataEksternal` (`IntegrasiEksternalId`,`JenisEntitas`,`EntitasId`),
  KEY `IdxPemetaanKodeEksternal` (`IntegrasiEksternalId`,`KodeEksternal`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`IntegrasiEksternalId`) REFERENCES `IntegrasiEksternal` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `SinkronisasiEksternal` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `IntegrasiEksternalId` CHAR(26) NOT NULL,
  `JenisProses` VARCHAR(80) NOT NULL,
  `Arah` VARCHAR(30) NOT NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Diproses',
  `JumlahData` INT UNSIGNED NOT NULL DEFAULT 0,
  `JumlahBerhasil` INT UNSIGNED NOT NULL DEFAULT 0,
  `JumlahGagal` INT UNSIGNED NOT NULL DEFAULT 0,
  `PesanKesalahan` TEXT NULL,
  `MulaiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `SelesaiPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  KEY `IdxSinkronisasiEksternal` (`IntegrasiEksternalId`,`MulaiPada`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`IntegrasiEksternalId`) REFERENCES `IntegrasiEksternal` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 13. APPROVAL ENGINE GENERIK
-- ============================================================

CREATE TABLE `AlurPersetujuan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `JenisEntitas` VARCHAR(80) NOT NULL,
  `KondisiAktivasi` JSON NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqAlurPersetujuan` (`OrganisasiId`,`Kode`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `TahapPersetujuan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AlurPersetujuanId` CHAR(26) NOT NULL,
  `Urutan` INT UNSIGNED NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `JenisPenyetuju` VARCHAR(50) NOT NULL,
  `PeranId` CHAR(26) NULL,
  `PenggunaId` CHAR(26) NULL,
  `JumlahMinimumPenyetuju` INT UNSIGNED NOT NULL DEFAULT 1,
  `BolehMenyetujuiSendiri` TINYINT(1) NOT NULL DEFAULT 0,
  `BatasWaktuMenit` INT UNSIGNED NULL,
  `Kondisi` JSON NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqTahapPersetujuanUrutan` (`AlurPersetujuanId`,`Urutan`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AlurPersetujuanId`) REFERENCES `AlurPersetujuan` (`Id`),
  FOREIGN KEY (`PeranId`) REFERENCES `Peran` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PermintaanPersetujuan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `AlurPersetujuanId` CHAR(26) NOT NULL,
  `JenisEntitas` VARCHAR(80) NOT NULL,
  `EntitasId` CHAR(26) NOT NULL,
  `TahapSaatIni` INT UNSIGNED NOT NULL DEFAULT 1,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Menunggu',
  `DimintaOleh` CHAR(26) NOT NULL,
  `DimintaPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `SelesaiPada` DATETIME(6) NULL,
  `DataTambahan` JSON NULL,
  PRIMARY KEY (`Id`),
  KEY `IdxPermintaanPersetujuanEntitas` (`OrganisasiId`,`JenisEntitas`,`EntitasId`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`AlurPersetujuanId`) REFERENCES `AlurPersetujuan` (`Id`),
  FOREIGN KEY (`DimintaOleh`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KeputusanPersetujuan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PermintaanPersetujuanId` CHAR(26) NOT NULL,
  `TahapPersetujuanId` CHAR(26) NOT NULL,
  `PenyetujuId` CHAR(26) NOT NULL,
  `Keputusan` VARCHAR(40) NOT NULL,
  `Catatan` TEXT NULL,
  `DiputuskanPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKeputusanPersetujuan` (`PermintaanPersetujuanId`,`TahapPersetujuanId`,`PenyetujuId`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PermintaanPersetujuanId`) REFERENCES `PermintaanPersetujuan` (`Id`),
  FOREIGN KEY (`TahapPersetujuanId`) REFERENCES `TahapPersetujuan` (`Id`),
  FOREIGN KEY (`PenyetujuId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 14. NOTIFIKASI & ESKALASI
-- ============================================================

CREATE TABLE `TemplatNotifikasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NULL,
  `Kode` VARCHAR(100) NOT NULL,
  `Kanal` VARCHAR(40) NOT NULL,
  `JudulTemplat` TEXT NULL,
  `IsiTemplat` LONGTEXT NOT NULL,
  `Variabel` JSON NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxTemplatNotifikasi` (`OrganisasiId`,`Kode`,`Kanal`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PreferensiNotifikasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PenggunaId` CHAR(26) NOT NULL,
  `JenisPeristiwa` VARCHAR(100) NOT NULL,
  `Kanal` VARCHAR(40) NOT NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPreferensiNotifikasi` (`PenggunaId`,`JenisPeristiwa`,`Kanal`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Notifikasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PenggunaId` CHAR(26) NULL,
  `Kanal` VARCHAR(40) NOT NULL,
  `JenisPeristiwa` VARCHAR(100) NOT NULL,
  `Judul` VARCHAR(255) NULL,
  `Isi` LONGTEXT NOT NULL,
  `JenisEntitas` VARCHAR(80) NULL,
  `EntitasId` CHAR(26) NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Antri',
  `JadwalKirimPada` DATETIME(6) NULL,
  `DikirimPada` DATETIME(6) NULL,
  `DibacaPada` DATETIME(6) NULL,
  `Percobaan` INT UNSIGNED NOT NULL DEFAULT 0,
  `KesalahanTerakhir` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxNotifikasiPengguna` (`PenggunaId`,`Status`,`DibuatPada`),
  KEY `IdxNotifikasiAntrian` (`OrganisasiId`,`Status`,`JadwalKirimPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `EskalasiTingkatLayanan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `TingkatLayananId` CHAR(26) NOT NULL,
  `Tahap` INT UNSIGNED NOT NULL,
  `Pemicu` VARCHAR(30) NOT NULL DEFAULT 'Terlewati',
  `SetelahMenit` INT UNSIGNED NOT NULL,
  `PeranId` CHAR(26) NULL,
  `PenggunaId` CHAR(26) NULL,
  `Kanal` JSON NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqEskalasiTingkatLayanan` (`TingkatLayananId`,`Tahap`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`TingkatLayananId`) REFERENCES `TingkatLayanan` (`Id`),
  FOREIGN KEY (`PeranId`) REFERENCES `Peran` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 15. WEBHOOK, OUTBOX, IDEMPOTENSI, AUDIT
-- ============================================================

CREATE TABLE `PanggilanBalikWeb` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `Url` TEXT NOT NULL,
  `Rahasia` VARCHAR(255) NULL,
  `Peristiwa` JSON NOT NULL,
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PengirimanPanggilanBalikWeb` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PanggilanBalikWebId` CHAR(26) NOT NULL,
  `Peristiwa` VARCHAR(120) NOT NULL,
  `MuatanData` JSON NOT NULL,
  `StatusHttp` SMALLINT UNSIGNED NULL,
  `Respons` LONGTEXT NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Antri',
  `Percobaan` INT UNSIGNED NOT NULL DEFAULT 0,
  `JadwalCobaLagiPada` DATETIME(6) NULL,
  `DikirimPada` DATETIME(6) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxPengirimanPanggilanBalikWebAntrian` (`Status`,`JadwalCobaLagiPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PanggilanBalikWebId`) REFERENCES `PanggilanBalikWeb` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KotakKeluarPeristiwa` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NULL,
  `NamaPeristiwa` VARCHAR(160) NOT NULL,
  `JenisAgregat` VARCHAR(100) NULL,
  `AgregatId` CHAR(26) NULL,
  `MuatanData` JSON NOT NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Menunggu',
  `Percobaan` INT UNSIGNED NOT NULL DEFAULT 0,
  `TersediaPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiprosesPada` DATETIME(6) NULL,
  `KesalahanTerakhir` TEXT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxKotakKeluarPeristiwa` (`Status`,`TersediaPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KunciIdempotensi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NULL,
  `Kunci` VARCHAR(255) NOT NULL,
  `Rute` VARCHAR(255) NOT NULL,
  `HashPermintaan` CHAR(64) NULL,
  `StatusHttp` SMALLINT UNSIGNED NULL,
  `Respons` LONGTEXT NULL,
  `KadaluarsaPada` DATETIME(6) NOT NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqKunciIdempotensi` (`OrganisasiId`,`Kunci`,`Rute`),
  KEY `IdxKunciIdempotensiKadaluarsa` (`KadaluarsaPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `CatatanAudit` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NULL,
  `PenggunaId` CHAR(26) NULL,
  `Aksi` VARCHAR(100) NOT NULL,
  `JenisEntitas` VARCHAR(100) NOT NULL,
  `EntitasId` CHAR(26) NULL,
  `DataSebelum` JSON NULL,
  `DataSesudah` JSON NULL,
  `AlamatIp` VARCHAR(64) NULL,
  `AgenPengguna` TEXT NULL,
  `KorelasiId` VARCHAR(100) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxCatatanAuditEntitas` (`OrganisasiId`,`JenisEntitas`,`EntitasId`,`DibuatPada`),
  KEY `IdxCatatanAuditPengguna` (`PenggunaId`,`DibuatPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `CatatanAkses` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NULL,
  `PenggunaId` CHAR(26) NULL,
  `Jenis` VARCHAR(80) NOT NULL,
  `AlamatIp` VARCHAR(64) NULL,
  `AgenPengguna` TEXT NULL,
  `Berhasil` TINYINT(1) NOT NULL DEFAULT 1,
  `AlasanGagal` VARCHAR(255) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxCatatanAksesPengguna` (`PenggunaId`,`DibuatPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PenggunaId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 16. OFFLINE SYNC / PWA
-- ============================================================

CREATE TABLE `AntrianSinkronisasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PerangkatPenggunaId` CHAR(26) NOT NULL,
  `KunciOperasi` VARCHAR(255) NOT NULL,
  `JenisEntitas` VARCHAR(80) NOT NULL,
  `EntitasId` CHAR(26) NULL,
  `Operasi` VARCHAR(30) NOT NULL,
  `VersiKlien` INT UNSIGNED NULL,
  `MuatanData` JSON NOT NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Menunggu',
  `Konflik` JSON NULL,
  `Percobaan` INT UNSIGNED NOT NULL DEFAULT 0,
  `DiterimaPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiprosesPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqAntrianSinkronisasiOperasi` (`PerangkatPenggunaId`,`KunciOperasi`),
  KEY `IdxAntrianSinkronisasi` (`OrganisasiId`,`Status`,`DiterimaPada`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PerangkatPenggunaId`) REFERENCES `PerangkatPengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PenandaSinkronisasi` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PerangkatPenggunaId` CHAR(26) NOT NULL,
  `JenisEntitas` VARCHAR(80) NOT NULL,
  `TokenSinkronisasi` VARCHAR(255) NULL,
  `TerakhirSinkronPada` DATETIME(6) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPenandaSinkronisasi` (`PerangkatPenggunaId`,`JenisEntitas`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PerangkatPenggunaId`) REFERENCES `PerangkatPengguna` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 17. LAPORAN & DASHBOARD
-- ============================================================

CREATE TABLE `LaporanTersimpan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `Jenis` VARCHAR(80) NOT NULL,
  `Konfigurasi` JSON NOT NULL,
  `Pribadi` TINYINT(1) NOT NULL DEFAULT 0,
  `PemilikId` CHAR(26) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PemilikId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `DasborTersimpan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `Nama` VARCHAR(180) NOT NULL,
  `PemilikId` CHAR(26) NULL,
  `Bawaan` TINYINT(1) NOT NULL DEFAULT 0,
  `Konfigurasi` JSON NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PemilikId`) REFERENCES `Pengguna` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `KomponenDasbor` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `DasborTersimpanId` CHAR(26) NOT NULL,
  `JenisKomponen` VARCHAR(80) NOT NULL,
  `Judul` VARCHAR(180) NULL,
  `Konfigurasi` JSON NOT NULL,
  `PosisiX` INT NOT NULL DEFAULT 0,
  `PosisiY` INT NOT NULL DEFAULT 0,
  `Lebar` INT NOT NULL DEFAULT 4,
  `Tinggi` INT NOT NULL DEFAULT 3,
  `Urutan` INT NOT NULL DEFAULT 0,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`DasborTersimpanId`) REFERENCES `DasborTersimpan` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 18. SAAS / LANGGANAN AMANPOLL
-- ============================================================

CREATE TABLE `FiturPaket` (
  `Id` CHAR(26) NOT NULL,
  `Kode` VARCHAR(100) NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `Deskripsi` TEXT NULL,
  `TipeBatas` VARCHAR(40) NOT NULL DEFAULT 'Boolean',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqFiturPaketKode` (`Kode`)
) ENGINE=InnoDB;

CREATE TABLE `PaketLangganan` (
  `Id` CHAR(26) NOT NULL,
  `Kode` VARCHAR(80) NOT NULL,
  `Nama` VARCHAR(160) NOT NULL,
  `Deskripsi` TEXT NULL,
  `HargaBulanan` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `HargaTahunan` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `MataUang` CHAR(3) NOT NULL DEFAULT 'IDR',
  `Aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPaketLanggananKode` (`Kode`)
) ENGINE=InnoDB;

CREATE TABLE `PaketFitur` (
  `Id` CHAR(26) NOT NULL,
  `PaketLanggananId` CHAR(26) NOT NULL,
  `FiturPaketId` CHAR(26) NOT NULL,
  `Diizinkan` TINYINT(1) NOT NULL DEFAULT 1,
  `BatasNilai` DECIMAL(20,4) NULL,
  `NilaiJson` JSON NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqPaketFitur` (`PaketLanggananId`,`FiturPaketId`),
  FOREIGN KEY (`PaketLanggananId`) REFERENCES `PaketLangganan` (`Id`),
  FOREIGN KEY (`FiturPaketId`) REFERENCES `FiturPaket` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `Langganan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `PaketLanggananId` CHAR(26) NOT NULL,
  `Siklus` VARCHAR(30) NOT NULL DEFAULT 'Bulanan',
  `MulaiPada` DATE NOT NULL,
  `BerakhirPada` DATE NULL,
  `UjiCobaSampai` DATE NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Aktif',
  `BatalPada` DATETIME(6) NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `DiperbaruiPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxLanggananOrganisasi` (`OrganisasiId`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`PaketLanggananId`) REFERENCES `PaketLangganan` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `TagihanLangganan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `LanggananId` CHAR(26) NOT NULL,
  `Nomor` VARCHAR(120) NOT NULL,
  `PeriodeMulai` DATE NOT NULL,
  `PeriodeSelesai` DATE NOT NULL,
  `JatuhTempo` DATE NOT NULL,
  `Subtotal` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Pajak` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Total` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'BelumDibayar',
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UqTagihanLanggananNomor` (`Nomor`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`LanggananId`) REFERENCES `Langganan` (`Id`)
) ENGINE=InnoDB;

CREATE TABLE `PembayaranLangganan` (
  `Id` CHAR(26) NOT NULL,
  `OrganisasiId` CHAR(26) NOT NULL,
  `TagihanLanggananId` CHAR(26) NOT NULL,
  `PenyediaPembayaran` VARCHAR(80) NULL,
  `ReferensiEksternal` VARCHAR(180) NULL,
  `Metode` VARCHAR(60) NULL,
  `Jumlah` DECIMAL(20,2) NOT NULL,
  `Status` VARCHAR(40) NOT NULL DEFAULT 'Menunggu',
  `DibayarPada` DATETIME(6) NULL,
  `MuatanData` JSON NULL,
  `DibuatPada` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`Id`),
  KEY `IdxPembayaranLangganan` (`TagihanLanggananId`,`Status`),
  FOREIGN KEY (`OrganisasiId`) REFERENCES `Organisasi` (`Id`),
  FOREIGN KEY (`TagihanLanggananId`) REFERENCES `TagihanLangganan` (`Id`)
) ENGINE=InnoDB;

-- ============================================================
-- 19. VIEW OPERASIONAL
-- ============================================================

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
WHERE a.`DihapusPada` IS NULL;

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
GROUP BY s.`OrganisasiId`, s.`Id`, s.`Kode`, s.`Nama`, s.`SatuanDasar`, s.`StokMinimum`;

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
WHERE pk.`DihapusPada` IS NULL;

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
WHERE a.`DihapusPada` IS NULL;

-- ============================================================
-- 20. SEED IZIN DASAR
-- CATATAN: Id memakai ULID statis valid 26 karakter.
-- ============================================================

INSERT INTO `Izin` (`Id`,`Kode`,`Nama`,`Modul`) VALUES
('01JAMANPOLL000000000000001','Aset.Lihat','Lihat Aset','Aset'),
('01JAMANPOLL000000000000002','Aset.Buat','Buat Aset','Aset'),
('01JAMANPOLL000000000000003','Aset.Ubah','Ubah Aset','Aset'),
('01JAMANPOLL000000000000004','Aset.Hapus','Hapus Aset','Aset'),
('01JAMANPOLL000000000000005','Keluhan.Kelola','Kelola Keluhan','Keluhan'),
('01JAMANPOLL000000000000006','PerintahKerja.Kelola','Kelola Perintah Kerja','PerintahKerja'),
('01JAMANPOLL000000000000007','Pemeliharaan.Kelola','Kelola Pemeliharaan','Pemeliharaan'),
('01JAMANPOLL000000000000008','Kalibrasi.Kelola','Kelola Kalibrasi','Kalibrasi'),
('01JAMANPOLL000000000000009','Stok.Kelola','Kelola Stok dan Suku Cadang','Persediaan'),
('01JAMANPOLL000000000000010','Pengadaan.Kelola','Kelola Pengadaan','Pengadaan'),
('01JAMANPOLL000000000000011','Penyedia.Kelola','Kelola Penyedia','Penyedia'),
('01JAMANPOLL000000000000012','Kontrak.Kelola','Kelola Kontrak','Kontrak'),
('01JAMANPOLL000000000000013','Persetujuan.Kelola','Kelola Persetujuan','Persetujuan'),
('01JAMANPOLL000000000000014','Laporan.Lihat','Lihat Laporan','Laporan'),
('01JAMANPOLL000000000000015','Pengguna.Kelola','Kelola Pengguna','IAM'),
('01JAMANPOLL000000000000016','Pengaturan.Kelola','Kelola Pengaturan','Sistem'),
('01JAMANPOLL000000000000017','Audit.Lihat','Lihat Audit','Audit'),
('01JAMANPOLL000000000000018','Integrasi.Kelola','Kelola Integrasi','Integrasi');

SET FOREIGN_KEY_CHECKS = 1;
