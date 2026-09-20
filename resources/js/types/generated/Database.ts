/* AUTO-GENERATED oleh index.js Amanpoll. Jangan edit manual. */

export interface Organisasi {
  Id: string;
  Kode: string;
  Nama: string;
  NamaLegal: string | null;
  JenisUsaha: string | null;
  NomorIdentitasPajak: string | null;
  Email: string | null;
  Telepon: string | null;
  Alamat: string | null;
  Negara: string | null;
  Provinsi: string | null;
  Kota: string | null;
  ZonaWaktu: string;
  LogoUrl: string | null;
  Status: string;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface UnitOrganisasi {
  Id: string;
  OrganisasiId: string;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  Jenis: string;
  Email: string | null;
  Telepon: string | null;
  Status: string;
  Urutan: number;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface KategoriLokasi {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface Lokasi {
  Id: string;
  OrganisasiId: string;
  UnitOrganisasiId: string | null;
  KategoriLokasiId: string | null;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  Alamat: string | null;
  Lantai: string | null;
  Latitude: number | null;
  Longitude: number | null;
  ZonaWaktu: string | null;
  Status: string;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface Pengguna {
  Id: string;
  OrganisasiId: string;
  UnitOrganisasiId: string | null;
  Nama: string;
  Email: string;
  Telepon: string | null;
  KataSandi: string | null;
  EmailTerverifikasiPada: string | null;
  AvatarUrl: string | null;
  NomorPegawai: string | null;
  Jabatan: string | null;
  JenisPengguna: string;
  Status: string;
  TerakhirMasukPada: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface Peran {
  Id: string;
  OrganisasiId: string | null;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  BawaanSistem: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface Izin {
  Id: string;
  Kode: string;
  Nama: string;
  Modul: string;
  Keterangan: string | null;
  DibuatPada: string;
}

export interface PenggunaPeran {
  Id: string;
  OrganisasiId: string;
  PenggunaId: string;
  PeranId: string;
  UnitOrganisasiId: string | null;
  LokasiId: string | null;
  BerlakuMulai: string | null;
  BerlakuSampai: string | null;
  DibuatPada: string;
}

export interface PeranIzin {
  Id: string;
  PeranId: string;
  IzinId: string;
  DibuatPada: string;
}

export interface PerangkatPengguna {
  Id: string;
  OrganisasiId: string;
  PenggunaId: string;
  NamaPerangkat: string | null;
  Platform: string | null;
  IdentitasPerangkat: string | null;
  TokenPush: string | null;
  TerakhirSinkronPada: string | null;
  Status: string;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface KunciApi {
  Id: string;
  OrganisasiId: string;
  Nama: string;
  AwalanKunci: string;
  HashKunci: string;
  Cakupan: Record<string, unknown> | unknown[] | null;
  AlamatIpDiizinkan: Record<string, unknown> | unknown[] | null;
  KadaluarsaPada: string | null;
  TerakhirDipakaiPada: string | null;
  Status: string;
  DibuatOleh: string | null;
  DibuatPada: string;
}

export interface KonfigurasiOrganisasi {
  Id: string;
  OrganisasiId: string;
  Kunci: string;
  Nilai: Record<string, unknown> | unknown[] | null;
  Rahasia: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface NomorDokumen {
  Id: string;
  OrganisasiId: string;
  JenisDokumen: string;
  Awalan: string | null;
  FormatNomor: string;
  NomorTerakhir: number;
  ResetPeriode: string;
  PeriodeAktif: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface HariLibur {
  Id: string;
  OrganisasiId: string;
  LokasiId: string | null;
  Tanggal: string;
  Nama: string;
  BerulangTahunan: boolean;
  DibuatPada: string;
}

export interface Berkas {
  Id: string;
  OrganisasiId: string;
  NamaAsli: string;
  NamaPenyimpanan: string;
  MediaPenyimpanan: string;
  LokasiPenyimpanan: string;
  JenisMime: string | null;
  UkuranByte: number | null;
  HashSha256: string | null;
  DataTambahan: Record<string, unknown> | unknown[] | null;
  DiunggahOleh: string | null;
  DibuatPada: string;
  DihapusPada: string | null;
}

export interface LampiranEntitas {
  Id: string;
  OrganisasiId: string;
  JenisEntitas: string;
  EntitasId: string;
  BerkasId: string;
  Kategori: string | null;
  Keterangan: string | null;
  DibuatOleh: string | null;
  DibuatPada: string;
}

export interface Tag {
  Id: string;
  OrganisasiId: string;
  Nama: string;
  Warna: string | null;
  DibuatPada: string;
}

export interface EntitasTag {
  Id: string;
  OrganisasiId: string;
  TagId: string;
  JenisEntitas: string;
  EntitasId: string;
  DibuatPada: string;
}

export interface DefinisiKolomKustom {
  Id: string;
  OrganisasiId: string;
  JenisEntitas: string;
  Kode: string;
  Label: string;
  TipeData: string;
  Wajib: boolean;
  Pilihan: Record<string, unknown> | unknown[] | null;
  AturanValidasi: Record<string, unknown> | unknown[] | null;
  NilaiBawaan: Record<string, unknown> | unknown[] | null;
  Urutan: number;
  Aktif: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface NilaiKolomKustom {
  Id: string;
  OrganisasiId: string;
  DefinisiKolomKustomId: string;
  JenisEntitas: string;
  EntitasId: string;
  Nilai: Record<string, unknown> | unknown[] | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface KomentarEntitas {
  Id: string;
  OrganisasiId: string;
  JenisEntitas: string;
  EntitasId: string;
  IndukKomentarId: string | null;
  Isi: string;
  DibuatOleh: string;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface KategoriPenyedia {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  DibuatPada: string;
}

export interface Penyedia {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  NamaLegal: string | null;
  NomorIdentitasPajak: string | null;
  Email: string | null;
  Telepon: string | null;
  Website: string | null;
  Alamat: string | null;
  Kota: string | null;
  Provinsi: string | null;
  Negara: string | null;
  Status: string;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface PenyediaKategori {
  Id: string;
  PenyediaId: string;
  KategoriPenyediaId: string;
  DibuatPada: string;
}

export interface KontakPenyedia {
  Id: string;
  OrganisasiId: string;
  PenyediaId: string;
  Nama: string;
  Jabatan: string | null;
  Email: string | null;
  Telepon: string | null;
  Utama: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface PenilaianPenyedia {
  Id: string;
  OrganisasiId: string;
  PenyediaId: string;
  PeriodeMulai: string;
  PeriodeSelesai: string;
  SkorKualitas: number | null;
  SkorKetepatanWaktu: number | null;
  SkorHarga: number | null;
  SkorLayanan: number | null;
  SkorTotal: number | null;
  Catatan: string | null;
  DinilaiOleh: string | null;
  DibuatPada: string;
}

export interface KategoriAset {
  Id: string;
  OrganisasiId: string;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  UmurManfaatBulan: number | null;
  MetodePenyusutanBawaan: string | null;
  PersentaseNilaiResidu: number | null;
  MemerlukanKalibrasi: boolean;
  MemerlukanPemeliharaan: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface Merek {
  Id: string;
  OrganisasiId: string | null;
  Nama: string;
  NegaraAsal: string | null;
  Website: string | null;
  DibuatPada: string;
}

export interface ModelAset {
  Id: string;
  OrganisasiId: string;
  KategoriAsetId: string;
  MerekId: string | null;
  KodeModel: string | null;
  Nama: string;
  Produsen: string | null;
  Spesifikasi: Record<string, unknown> | unknown[] | null;
  IntervalPemeliharaanHari: number | null;
  IntervalKalibrasiHari: number | null;
  UmurManfaatBulan: number | null;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface Aset {
  Id: string;
  OrganisasiId: string;
  UnitOrganisasiId: string | null;
  LokasiId: string | null;
  KategoriAsetId: string;
  ModelAsetId: string | null;
  PenyediaId: string | null;
  KodeAset: string;
  Nama: string;
  NomorSeri: string | null;
  NomorInventaris: string | null;
  NomorRegistrasiEksternal: string | null;
  TanggalPerolehan: string | null;
  TanggalMulaiOperasi: string | null;
  TanggalAkhirOperasi: string | null;
  HargaPerolehan: number | null;
  NilaiResidu: number | null;
  MataUang: string;
  SumberDana: string | null;
  MetodePenyusutan: string | null;
  UmurManfaatBulan: number | null;
  Status: string;
  Kondisi: string;
  TingkatKritis: string;
  KodeQr: string | null;
  NfcUid: string | null;
  KodeBatang: string | null;
  Catatan: string | null;
  Versi: number;
  DibuatOleh: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface RelasiAset {
  Id: string;
  OrganisasiId: string;
  AsetIndukId: string;
  AsetAnakId: string;
  JenisRelasi: string;
  Jumlah: number;
  MulaiPada: string | null;
  SelesaiPada: string | null;
  DibuatPada: string;
}

export interface RiwayatLokasiAset {
  Id: string;
  OrganisasiId: string;
  AsetId: string;
  LokasiAsalId: string | null;
  LokasiTujuanId: string | null;
  JenisPerpindahan: string;
  ReferensiJenis: string | null;
  ReferensiId: string | null;
  Alasan: string | null;
  DipindahkanOleh: string | null;
  DipindahkanPada: string;
  DibuatPada: string;
}

export interface RiwayatPenanggungJawabAset {
  Id: string;
  OrganisasiId: string;
  AsetId: string;
  PenggunaId: string | null;
  UnitOrganisasiId: string | null;
  MulaiPada: string;
  SelesaiPada: string | null;
  Catatan: string | null;
  DibuatPada: string;
}

export interface GaransiAset {
  Id: string;
  OrganisasiId: string;
  AsetId: string;
  PenyediaId: string | null;
  NomorGaransi: string | null;
  JenisGaransi: string | null;
  MulaiPada: string;
  BerakhirPada: string;
  Cakupan: string | null;
  Status: string;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface NilaiAset {
  Id: string;
  OrganisasiId: string;
  AsetId: string;
  TanggalNilai: string;
  NilaiBuku: number;
  AkumulasiPenyusutan: number;
  BebanPenyusutanPeriode: number;
  Metode: string | null;
  DibuatPada: string;
}

export interface MeterAset {
  Id: string;
  OrganisasiId: string;
  AsetId: string;
  Nama: string;
  Satuan: string;
  Jenis: string;
  NilaiAwal: number;
  Aktif: boolean;
  DibuatPada: string;
}

export interface PembacaanMeterAset {
  Id: string;
  OrganisasiId: string;
  MeterAsetId: string;
  Nilai: number;
  DibacaPada: string;
  Sumber: string;
  DicatatOleh: string | null;
  DibuatPada: string;
}

export interface PermintaanMutasiAset {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  JenisMutasi: string;
  UnitAsalId: string | null;
  UnitTujuanId: string | null;
  LokasiAsalId: string | null;
  LokasiTujuanId: string | null;
  Alasan: string | null;
  Status: string;
  DimintaOleh: string;
  DimintaPada: string;
  DisetujuiPada: string | null;
  SelesaiPada: string | null;
  Versi: number;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface DetailMutasiAset {
  Id: string;
  OrganisasiId: string;
  PermintaanMutasiAsetId: string;
  AsetId: string;
  Status: string;
  Catatan: string | null;
  DibuatPada: string;
}

export interface SerahTerimaAset {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  PermintaanMutasiAsetId: string | null;
  Jenis: string;
  PihakMenyerahkan: string | null;
  PihakMenerima: string | null;
  DiserahkanPada: string | null;
  DiterimaPada: string | null;
  Status: string;
  Catatan: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface DetailSerahTerimaAset {
  Id: string;
  OrganisasiId: string;
  SerahTerimaAsetId: string;
  AsetId: string;
  KondisiSaatDiserahkan: string | null;
  KondisiSaatDiterima: string | null;
  Catatan: string | null;
  DibuatPada: string;
}

export interface PengajuanPenghapusanAset {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  Alasan: string;
  MetodePenghapusan: string | null;
  Status: string;
  DiajukanOleh: string;
  DiajukanPada: string;
  DiselesaikanPada: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface DetailPenghapusanAset {
  Id: string;
  OrganisasiId: string;
  PengajuanPenghapusanAsetId: string;
  AsetId: string;
  NilaiBukuSaatPenghapusan: number | null;
  HasilPelepasan: number | null;
  Status: string;
  Catatan: string | null;
  DibuatPada: string;
}

export interface TingkatLayanan {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  Deskripsi: string | null;
  HariKerja: Record<string, unknown> | unknown[] | null;
  JamKerjaMulai: string;
  JamKerjaSelesai: string;
  MemperhitungkanHariLibur: boolean;
  Aktif: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface AturanTingkatLayanan {
  Id: string;
  OrganisasiId: string;
  TingkatLayananId: string;
  Prioritas: string;
  MenitRespons: number | null;
  MenitMulaiPengerjaan: number | null;
  MenitPenyelesaian: number | null;
  MenghitungJamKerja: boolean;
  DibuatPada: string;
}

export interface KategoriKeluhan {
  Id: string;
  OrganisasiId: string;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  TingkatLayananId: string | null;
  PrioritasBawaan: string;
  AsetWajib: boolean;
  PeranPenanggungJawabId: string | null;
  Aktif: boolean;
  DibuatPada: string;
}

export interface Keluhan {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  KategoriKeluhanId: string | null;
  TingkatLayananId: string | null;
  AsetId: string | null;
  LokasiId: string | null;
  Judul: string;
  Deskripsi: string;
  Prioritas: string;
  Status: string;
  Sumber: string;
  PelaporId: string | null;
  NamaPelaporEksternal: string | null;
  KontakPelaporEksternal: string | null;
  DilaporkanPada: string;
  DiresponsPada: string | null;
  BatasResponsPada: string | null;
  BatasPenyelesaianPada: string | null;
  DiresolusikanPada: string | null;
  DitutupPada: string | null;
  Rating: string | null;
  Ulasan: string | null;
  Versi: number;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface RiwayatStatusKeluhan {
  Id: string;
  OrganisasiId: string;
  KeluhanId: string;
  StatusSebelum: string | null;
  StatusSesudah: string;
  Catatan: string | null;
  DiubahOleh: string | null;
  DiubahPada: string;
}

export interface PerintahKerja {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  KeluhanId: string | null;
  TingkatLayananId: string | null;
  Jenis: string;
  Judul: string;
  Deskripsi: string | null;
  Prioritas: string;
  Status: string;
  LokasiId: string | null;
  UnitOrganisasiId: string | null;
  DijadwalkanMulaiPada: string | null;
  DijadwalkanSelesaiPada: string | null;
  DiterimaPada: string | null;
  DimulaiPada: string | null;
  DiselesaikanPada: string | null;
  DitutupPada: string | null;
  BatasResponsPada: string | null;
  BatasPenyelesaianPada: string | null;
  PersentaseSelesai: number;
  MembutuhkanWaktuHenti: boolean;
  MembutuhkanPersetujuan: boolean;
  RingkasanPenyelesaian: string | null;
  DibuatOleh: string | null;
  Versi: number;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface PerintahKerjaAset {
  Id: string;
  OrganisasiId: string;
  PerintahKerjaId: string;
  AsetId: string;
  Utama: boolean;
  KondisiAwal: string | null;
  KondisiAkhir: string | null;
  DibuatPada: string;
}

export interface PenugasanPerintahKerja {
  Id: string;
  OrganisasiId: string;
  PerintahKerjaId: string;
  PenggunaId: string;
  PeranTugas: string;
  DitugaskanOleh: string | null;
  DitugaskanPada: string;
  DiterimaPada: string | null;
  SelesaiPada: string | null;
  Status: string;
}

export interface RiwayatStatusPerintahKerja {
  Id: string;
  OrganisasiId: string;
  PerintahKerjaId: string;
  StatusSebelum: string | null;
  StatusSesudah: string;
  Catatan: string | null;
  DiubahOleh: string | null;
  DiubahPada: string;
}

export interface WaktuKerja {
  Id: string;
  OrganisasiId: string;
  PerintahKerjaId: string;
  PenggunaId: string;
  MulaiPada: string;
  SelesaiPada: string | null;
  DurasiMenit: number | null;
  JenisWaktu: string;
  Catatan: string | null;
  DibuatPada: string;
}

export interface WaktuHentiAset {
  Id: string;
  OrganisasiId: string;
  AsetId: string;
  PerintahKerjaId: string | null;
  MulaiPada: string;
  SelesaiPada: string | null;
  DurasiMenit: number | null;
  Jenis: string;
  Alasan: string | null;
  DibuatPada: string;
}

export interface BiayaPerintahKerja {
  Id: string;
  OrganisasiId: string;
  PerintahKerjaId: string;
  JenisBiaya: string;
  Deskripsi: string | null;
  Jumlah: number;
  MataUang: string;
  PenyediaId: string | null;
  TanggalBiaya: string;
  DibuatOleh: string | null;
  DibuatPada: string;
}

export interface KodeKegagalan {
  Id: string;
  OrganisasiId: string;
  KategoriAsetId: string | null;
  Kode: string;
  Nama: string;
  Jenis: string;
  Keterangan: string | null;
  Aktif: boolean;
  DibuatPada: string;
}

export interface AnalisisKegagalan {
  Id: string;
  OrganisasiId: string;
  PerintahKerjaId: string;
  KodeMasalahId: string | null;
  KodePenyebabId: string | null;
  KodeTindakanId: string | null;
  AkarMasalah: string | null;
  TindakanKorektif: string | null;
  TindakanPencegahan: string | null;
  DibuatOleh: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface TemplatDaftarPeriksa {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  Jenis: string;
  KategoriAsetId: string | null;
  ModelAsetId: string | null;
  VersiTemplat: number;
  Aktif: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface ButirTemplatDaftarPeriksa {
  Id: string;
  OrganisasiId: string;
  TemplatDaftarPeriksaId: string;
  Urutan: number;
  Kode: string | null;
  Pertanyaan: string;
  TipeJawaban: string;
  Satuan: string | null;
  Wajib: boolean;
  NilaiMinimum: number | null;
  NilaiMaksimum: number | null;
  Pilihan: Record<string, unknown> | unknown[] | null;
  BuktiFotoWajib: boolean;
  MemicuTemuanJika: Record<string, unknown> | unknown[] | null;
  DibuatPada: string;
}

export interface PelaksanaanDaftarPeriksa {
  Id: string;
  OrganisasiId: string;
  TemplatDaftarPeriksaId: string;
  PerintahKerjaId: string | null;
  AsetId: string | null;
  DilaksanakanOleh: string | null;
  MulaiPada: string | null;
  SelesaiPada: string | null;
  Status: string;
  Skor: number | null;
  Catatan: string | null;
  DibuatPada: string;
}

export interface JawabanDaftarPeriksa {
  Id: string;
  OrganisasiId: string;
  PelaksanaanDaftarPeriksaId: string;
  ButirTemplatDaftarPeriksaId: string;
  NilaiTeks: string | null;
  NilaiAngka: number | null;
  NilaiBoolean: boolean | null;
  NilaiTanggal: string | null;
  NilaiJson: Record<string, unknown> | unknown[] | null;
  Sesuai: boolean | null;
  Catatan: string | null;
  DijawabPada: string | null;
}

export interface RencanaPemeliharaan {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  Jenis: string;
  TemplatDaftarPeriksaId: string | null;
  Prioritas: string;
  StrategiJadwal: string;
  IntervalNilai: number | null;
  IntervalSatuan: string | null;
  BerdasarkanMeter: boolean;
  AmbangMeter: number | null;
  ToleransiHari: number;
  BuatPerintahKerjaHariSebelum: number;
  Aktif: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface RencanaPemeliharaanAset {
  Id: string;
  OrganisasiId: string;
  RencanaPemeliharaanId: string;
  AsetId: string;
  TanggalMulai: string;
  TanggalBerikutnya: string | null;
  NilaiMeterBerikutnya: number | null;
  TerakhirDilaksanakanPada: string | null;
  Aktif: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface JadwalPemeliharaan {
  Id: string;
  OrganisasiId: string;
  RencanaPemeliharaanAsetId: string;
  PerintahKerjaId: string | null;
  TanggalJadwal: string;
  Status: string;
  DihasilkanOtomatis: boolean;
  DibuatPada: string;
}

export interface TemplatInspeksi {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  KategoriAsetId: string | null;
  TemplatDaftarPeriksaId: string;
  IntervalHari: number | null;
  Aktif: boolean;
  DibuatPada: string;
}

export interface Inspeksi {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  TemplatInspeksiId: string;
  AsetId: string;
  PelaksanaanDaftarPeriksaId: string | null;
  DijadwalkanPada: string | null;
  DilaksanakanPada: string | null;
  Status: string;
  Hasil: string | null;
  Temuan: string | null;
  TindakLanjut: string | null;
  PerintahKerjaId: string | null;
  DilaksanakanOleh: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface JenisKalibrasi {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  Deskripsi: string | null;
  Aktif: boolean;
  DibuatPada: string;
}

export interface RencanaKalibrasi {
  Id: string;
  OrganisasiId: string;
  AsetId: string;
  JenisKalibrasiId: string | null;
  PenyediaId: string | null;
  IntervalHari: number;
  TanggalMulai: string;
  TanggalBerikutnya: string;
  PeringatanHariSebelum: number;
  Aktif: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface PelaksanaanKalibrasi {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  RencanaKalibrasiId: string | null;
  AsetId: string;
  JenisKalibrasiId: string | null;
  PenyediaId: string | null;
  PerintahKerjaId: string | null;
  TanggalKalibrasi: string;
  TanggalBerlakuSampai: string | null;
  Hasil: string;
  NomorSertifikat: string | null;
  Laboratorium: string | null;
  KondisiLingkungan: Record<string, unknown> | unknown[] | null;
  Catatan: string | null;
  DilaksanakanOleh: string | null;
  DiverifikasiOleh: string | null;
  DiverifikasiPada: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface TitikUkurKalibrasi {
  Id: string;
  OrganisasiId: string;
  JenisKalibrasiId: string | null;
  KategoriAsetId: string | null;
  Nama: string;
  Satuan: string | null;
  NilaiReferensi: number | null;
  ToleransiMinus: number | null;
  ToleransiPlus: number | null;
  Urutan: number;
  Aktif: boolean;
  DibuatPada: string;
}

export interface HasilTitikUkurKalibrasi {
  Id: string;
  OrganisasiId: string;
  PelaksanaanKalibrasiId: string;
  TitikUkurKalibrasiId: string | null;
  NamaTitik: string | null;
  NilaiReferensi: number | null;
  NilaiTerukur: number | null;
  Koreksi: number | null;
  Ketidakpastian: number | null;
  Satuan: string | null;
  Hasil: string | null;
  Catatan: string | null;
  DibuatPada: string;
}

export interface Gudang {
  Id: string;
  OrganisasiId: string;
  LokasiId: string | null;
  Kode: string;
  Nama: string;
  PenanggungJawabId: string | null;
  Status: string;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface LokasiGudang {
  Id: string;
  OrganisasiId: string;
  GudangId: string;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  DibuatPada: string;
}

export interface KategoriSukuCadang {
  Id: string;
  OrganisasiId: string;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  DibuatPada: string;
}

export interface SukuCadang {
  Id: string;
  OrganisasiId: string;
  KategoriSukuCadangId: string | null;
  Kode: string;
  Nama: string;
  NomorBagian: string | null;
  KodeBatang: string | null;
  SatuanDasar: string;
  StokMinimum: number;
  StokMaksimum: number | null;
  TitikPesanUlang: number | null;
  HargaRataRata: number;
  MemakaiBatch: boolean;
  MemakaiKadaluarsa: boolean;
  Status: string;
  DibuatPada: string;
  DiperbaruiPada: string;
  DihapusPada: string | null;
}

export interface KompatibilitasSukuCadang {
  Id: string;
  OrganisasiId: string;
  SukuCadangId: string;
  KategoriAsetId: string | null;
  ModelAsetId: string | null;
  AsetId: string | null;
  Catatan: string | null;
  DibuatPada: string;
}

export interface KelompokSukuCadang {
  Id: string;
  OrganisasiId: string;
  SukuCadangId: string;
  NomorBatch: string;
  TanggalProduksi: string | null;
  TanggalKadaluarsa: string | null;
  HargaPerolehan: number | null;
  DibuatPada: string;
}

export interface StokSukuCadang {
  Id: string;
  OrganisasiId: string;
  GudangId: string;
  LokasiGudangId: string | null;
  SukuCadangId: string;
  KelompokSukuCadangId: string | null;
  JumlahTersedia: number;
  JumlahDipesan: number;
  JumlahDitahan: number;
  Versi: number;
  DiperbaruiPada: string;
}

export interface MutasiStok {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  Jenis: string;
  GudangAsalId: string | null;
  GudangTujuanId: string | null;
  ReferensiJenis: string | null;
  ReferensiId: string | null;
  Tanggal: string;
  Status: string;
  Catatan: string | null;
  DibuatOleh: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface DetailMutasiStok {
  Id: string;
  OrganisasiId: string;
  MutasiStokId: string;
  SukuCadangId: string;
  KelompokSukuCadangId: string | null;
  Jumlah: number;
  HargaSatuan: number | null;
  LokasiGudangAsalId: string | null;
  LokasiGudangTujuanId: string | null;
  DibuatPada: string;
}

export interface PemakaianSukuCadang {
  Id: string;
  OrganisasiId: string;
  PerintahKerjaId: string;
  SukuCadangId: string;
  GudangId: string | null;
  KelompokSukuCadangId: string | null;
  Jumlah: number;
  HargaSatuan: number | null;
  MutasiStokId: string | null;
  DipakaiOleh: string | null;
  DipakaiPada: string;
}

export interface ReservasiSukuCadang {
  Id: string;
  OrganisasiId: string;
  PerintahKerjaId: string | null;
  GudangId: string;
  SukuCadangId: string;
  Jumlah: number;
  Status: string;
  KadaluarsaPada: string | null;
  DibuatOleh: string | null;
  DibuatPada: string;
}

export interface Anggaran {
  Id: string;
  OrganisasiId: string;
  UnitOrganisasiId: string | null;
  Kode: string;
  Nama: string;
  Tahun: number;
  MataUang: string;
  Jumlah: number;
  Status: string;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface PosAnggaran {
  Id: string;
  OrganisasiId: string;
  AnggaranId: string;
  IndukId: string | null;
  Kode: string;
  Nama: string;
  Jumlah: number;
  Terpakai: number;
  Ditahan: number;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface TransaksiAnggaran {
  Id: string;
  OrganisasiId: string;
  PosAnggaranId: string;
  Jenis: string;
  ReferensiJenis: string | null;
  ReferensiId: string | null;
  Jumlah: number;
  Tanggal: string;
  Keterangan: string | null;
  DibuatPada: string;
}

export interface UsulanAset {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  UnitOrganisasiId: string;
  KategoriAsetId: string | null;
  ModelAsetId: string | null;
  NamaKebutuhan: string;
  Jumlah: number;
  EstimasiHargaSatuan: number | null;
  Alasan: string;
  JenisKebutuhan: string | null;
  TahunKebutuhan: number | null;
  Prioritas: string;
  Status: string;
  DiajukanOleh: string;
  DiajukanPada: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface PenilaianUsulanAset {
  Id: string;
  OrganisasiId: string;
  UsulanAsetId: string;
  Kriteria: string;
  Bobot: number;
  Nilai: number;
  Skor: number;
  DinilaiOleh: string | null;
  DinilaiPada: string;
}

export interface RencanaPengadaan {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  Nama: string;
  Tahun: number;
  PosAnggaranId: string | null;
  Status: string;
  TotalEstimasi: number;
  DibuatOleh: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface DetailRencanaPengadaan {
  Id: string;
  OrganisasiId: string;
  RencanaPengadaanId: string;
  UsulanAsetId: string | null;
  SukuCadangId: string | null;
  Deskripsi: string;
  Jumlah: number;
  Satuan: string;
  HargaEstimasi: number | null;
  BulanRencana: string | null;
  DibuatPada: string;
}

export interface PermintaanPembelian {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  UnitOrganisasiId: string | null;
  RencanaPengadaanId: string | null;
  PosAnggaranId: string | null;
  TanggalPermintaan: string;
  TanggalDibutuhkan: string | null;
  Prioritas: string;
  Status: string;
  Alasan: string | null;
  DimintaOleh: string;
  TotalEstimasi: number;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface DetailPermintaanPembelian {
  Id: string;
  OrganisasiId: string;
  PermintaanPembelianId: string;
  JenisItem: string;
  AsetReferensiId: string | null;
  SukuCadangId: string | null;
  Deskripsi: string;
  Jumlah: number;
  Satuan: string;
  HargaEstimasi: number | null;
  Spesifikasi: string | null;
  DibuatPada: string;
}

export interface PermintaanPenawaran {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  PermintaanPembelianId: string | null;
  TanggalDibuka: string;
  BatasPenawaran: string | null;
  Status: string;
  Catatan: string | null;
  DibuatOleh: string | null;
  DibuatPada: string;
}

export interface PenyediaPermintaanPenawaran {
  Id: string;
  OrganisasiId: string;
  PermintaanPenawaranId: string;
  PenyediaId: string;
  DikirimPada: string | null;
  DilihatPada: string | null;
  Status: string;
}

export interface PenawaranPenyedia {
  Id: string;
  OrganisasiId: string;
  PermintaanPenawaranId: string;
  PenyediaId: string;
  NomorPenawaran: string | null;
  TanggalPenawaran: string;
  BerlakuSampai: string | null;
  MataUang: string;
  Subtotal: number;
  Pajak: number;
  Diskon: number;
  Total: number;
  Status: string;
  Catatan: string | null;
  DibuatPada: string;
}

export interface DetailPenawaranPenyedia {
  Id: string;
  OrganisasiId: string;
  PenawaranPenyediaId: string;
  DetailPermintaanPembelianId: string | null;
  Deskripsi: string;
  Jumlah: number;
  HargaSatuan: number;
  Diskon: number;
  Pajak: number;
  Total: number;
  WaktuPengirimanHari: number | null;
  DibuatPada: string;
}

export interface PesananPembelian {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  PenyediaId: string;
  PermintaanPembelianId: string | null;
  PenawaranPenyediaId: string | null;
  PosAnggaranId: string | null;
  TanggalPesanan: string;
  TanggalKirimRencana: string | null;
  MataUang: string;
  Subtotal: number;
  Pajak: number;
  Diskon: number;
  Total: number;
  Status: string;
  Catatan: string | null;
  DibuatOleh: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface DetailPesananPembelian {
  Id: string;
  OrganisasiId: string;
  PesananPembelianId: string;
  JenisItem: string;
  SukuCadangId: string | null;
  Deskripsi: string;
  Jumlah: number;
  Satuan: string;
  HargaSatuan: number;
  Diskon: number;
  Pajak: number;
  Total: number;
  DibuatPada: string;
}

export interface PenerimaanPembelian {
  Id: string;
  OrganisasiId: string;
  Nomor: string;
  PesananPembelianId: string;
  GudangId: string | null;
  TanggalTerima: string;
  NomorSuratJalan: string | null;
  DiterimaOleh: string | null;
  Status: string;
  Catatan: string | null;
  DibuatPada: string;
}

export interface DetailPenerimaanPembelian {
  Id: string;
  OrganisasiId: string;
  PenerimaanPembelianId: string;
  DetailPesananPembelianId: string | null;
  SukuCadangId: string | null;
  JumlahDipesan: number;
  JumlahDiterima: number;
  JumlahDitolak: number;
  Kondisi: string | null;
  NomorSeriJson: Record<string, unknown> | unknown[] | null;
  Catatan: string | null;
  DibuatPada: string;
}

export interface TagihanPenyedia {
  Id: string;
  OrganisasiId: string;
  PenyediaId: string;
  PesananPembelianId: string | null;
  NomorTagihan: string;
  TanggalTagihan: string;
  JatuhTempo: string | null;
  Subtotal: number;
  Pajak: number;
  Total: number;
  Sisa: number;
  Status: string;
  DibuatPada: string;
}

export interface PembayaranPenyedia {
  Id: string;
  OrganisasiId: string;
  TagihanPenyediaId: string;
  NomorPembayaran: string;
  TanggalBayar: string;
  Jumlah: number;
  Metode: string | null;
  Referensi: string | null;
  DibuatOleh: string | null;
  DibuatPada: string;
}

export interface Kontrak {
  Id: string;
  OrganisasiId: string;
  PenyediaId: string | null;
  Nomor: string;
  Nama: string;
  Jenis: string;
  MulaiPada: string;
  BerakhirPada: string;
  Nilai: number | null;
  MataUang: string;
  TingkatLayananId: string | null;
  PeringatanHariSebelum: number;
  Status: string;
  Catatan: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface KontrakAset {
  Id: string;
  OrganisasiId: string;
  KontrakId: string;
  AsetId: string;
  MulaiPada: string | null;
  BerakhirPada: string | null;
  Catatan: string | null;
  DibuatPada: string;
}

export interface LayananKontrak {
  Id: string;
  OrganisasiId: string;
  KontrakId: string;
  Nama: string;
  Deskripsi: string | null;
  Kuota: number | null;
  Satuan: string | null;
  Terpakai: number;
  DibuatPada: string;
}

export interface StandarKepatuhan {
  Id: string;
  OrganisasiId: string | null;
  Kode: string;
  Nama: string;
  Penerbit: string | null;
  VersiStandar: string | null;
  JenisIndustri: string | null;
  Deskripsi: string | null;
  Aktif: boolean;
  DibuatPada: string;
}

export interface PersyaratanKepatuhan {
  Id: string;
  OrganisasiId: string | null;
  StandarKepatuhanId: string;
  Kode: string;
  Nama: string;
  Deskripsi: string | null;
  BuktiYangDiperlukan: string | null;
  IntervalHari: number | null;
  DibuatPada: string;
}

export interface KepatuhanAset {
  Id: string;
  OrganisasiId: string;
  AsetId: string;
  PersyaratanKepatuhanId: string;
  Status: string;
  TanggalPemeriksaan: string | null;
  BerlakuSampai: string | null;
  Catatan: string | null;
  DiperiksaOleh: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface SertifikasiAset {
  Id: string;
  OrganisasiId: string;
  AsetId: string;
  JenisSertifikasi: string;
  NomorSertifikat: string | null;
  Penerbit: string | null;
  TerbitPada: string | null;
  BerlakuSampai: string | null;
  Status: string;
  BerkasId: string | null;
  DibuatPada: string;
}

export interface IntegrasiEksternal {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  Jenis: string;
  UrlDasar: string | null;
  MetodeAutentikasi: string | null;
  KonfigurasiTerenkripsi: Record<string, unknown> | unknown[] | null;
  Status: string;
  TerakhirSinkronPada: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface PemetaanDataEksternal {
  Id: string;
  OrganisasiId: string;
  IntegrasiEksternalId: string;
  JenisEntitas: string;
  EntitasId: string;
  KodeEksternal: string;
  DataTambahan: Record<string, unknown> | unknown[] | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface SinkronisasiEksternal {
  Id: string;
  OrganisasiId: string;
  IntegrasiEksternalId: string;
  JenisProses: string;
  Arah: string;
  Status: string;
  JumlahData: number;
  JumlahBerhasil: number;
  JumlahGagal: number;
  PesanKesalahan: string | null;
  MulaiPada: string;
  SelesaiPada: string | null;
}

export interface AlurPersetujuan {
  Id: string;
  OrganisasiId: string;
  Kode: string;
  Nama: string;
  JenisEntitas: string;
  KondisiAktivasi: Record<string, unknown> | unknown[] | null;
  Aktif: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface TahapPersetujuan {
  Id: string;
  OrganisasiId: string;
  AlurPersetujuanId: string;
  Urutan: number;
  Nama: string;
  JenisPenyetuju: string;
  PeranId: string | null;
  PenggunaId: string | null;
  JumlahMinimumPenyetuju: number;
  BolehMenyetujuiSendiri: boolean;
  BatasWaktuMenit: number | null;
  Kondisi: Record<string, unknown> | unknown[] | null;
  DibuatPada: string;
}

export interface PermintaanPersetujuan {
  Id: string;
  OrganisasiId: string;
  AlurPersetujuanId: string;
  JenisEntitas: string;
  EntitasId: string;
  TahapSaatIni: number;
  Status: string;
  DimintaOleh: string;
  DimintaPada: string;
  SelesaiPada: string | null;
  DataTambahan: Record<string, unknown> | unknown[] | null;
}

export interface KeputusanPersetujuan {
  Id: string;
  OrganisasiId: string;
  PermintaanPersetujuanId: string;
  TahapPersetujuanId: string;
  PenyetujuId: string;
  Keputusan: string;
  Catatan: string | null;
  DiputuskanPada: string;
}

export interface TemplatNotifikasi {
  Id: string;
  OrganisasiId: string | null;
  Kode: string;
  Kanal: string;
  JudulTemplat: string | null;
  IsiTemplat: string;
  Variabel: Record<string, unknown> | unknown[] | null;
  Aktif: boolean;
  DibuatPada: string;
}

export interface PreferensiNotifikasi {
  Id: string;
  OrganisasiId: string;
  PenggunaId: string;
  JenisPeristiwa: string;
  Kanal: string;
  Aktif: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface Notifikasi {
  Id: string;
  OrganisasiId: string;
  PenggunaId: string | null;
  Kanal: string;
  JenisPeristiwa: string;
  Judul: string | null;
  Isi: string;
  JenisEntitas: string | null;
  EntitasId: string | null;
  Status: string;
  JadwalKirimPada: string | null;
  DikirimPada: string | null;
  DibacaPada: string | null;
  Percobaan: number;
  KesalahanTerakhir: string | null;
  DibuatPada: string;
}

export interface EskalasiTingkatLayanan {
  Id: string;
  OrganisasiId: string;
  TingkatLayananId: string;
  Tahap: number;
  Pemicu: string;
  SetelahMenit: number;
  PeranId: string | null;
  PenggunaId: string | null;
  Kanal: Record<string, unknown> | unknown[] | null;
  Aktif: boolean;
  DibuatPada: string;
}

export interface PanggilanBalikWeb {
  Id: string;
  OrganisasiId: string;
  Nama: string;
  Url: string;
  Rahasia: string | null;
  Peristiwa: Record<string, unknown> | unknown[];
  Aktif: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface PengirimanPanggilanBalikWeb {
  Id: string;
  OrganisasiId: string;
  PanggilanBalikWebId: string;
  Peristiwa: string;
  MuatanData: Record<string, unknown> | unknown[];
  StatusHttp: number | null;
  Respons: string | null;
  Status: string;
  Percobaan: number;
  JadwalCobaLagiPada: string | null;
  DikirimPada: string | null;
  DibuatPada: string;
}

export interface KotakKeluarPeristiwa {
  Id: string;
  OrganisasiId: string | null;
  NamaPeristiwa: string;
  JenisAgregat: string | null;
  AgregatId: string | null;
  MuatanData: Record<string, unknown> | unknown[];
  Status: string;
  Percobaan: number;
  TersediaPada: string;
  DiprosesPada: string | null;
  KesalahanTerakhir: string | null;
  DibuatPada: string;
}

export interface KunciIdempotensi {
  Id: string;
  OrganisasiId: string | null;
  Kunci: string;
  Rute: string;
  HashPermintaan: string | null;
  StatusHttp: number | null;
  Respons: string | null;
  KadaluarsaPada: string;
  DibuatPada: string;
}

export interface CatatanAudit {
  Id: string;
  OrganisasiId: string | null;
  PenggunaId: string | null;
  Aksi: string;
  JenisEntitas: string;
  EntitasId: string | null;
  DataSebelum: Record<string, unknown> | unknown[] | null;
  DataSesudah: Record<string, unknown> | unknown[] | null;
  AlamatIp: string | null;
  AgenPengguna: string | null;
  KorelasiId: string | null;
  DibuatPada: string;
}

export interface CatatanAkses {
  Id: string;
  OrganisasiId: string | null;
  PenggunaId: string | null;
  Jenis: string;
  AlamatIp: string | null;
  AgenPengguna: string | null;
  Berhasil: boolean;
  AlasanGagal: string | null;
  DibuatPada: string;
}

export interface AntrianSinkronisasi {
  Id: string;
  OrganisasiId: string;
  PerangkatPenggunaId: string;
  KunciOperasi: string;
  JenisEntitas: string;
  EntitasId: string | null;
  Operasi: string;
  VersiKlien: number | null;
  MuatanData: Record<string, unknown> | unknown[];
  Status: string;
  Konflik: Record<string, unknown> | unknown[] | null;
  Percobaan: number;
  DiterimaPada: string;
  DiprosesPada: string | null;
}

export interface PenandaSinkronisasi {
  Id: string;
  OrganisasiId: string;
  PerangkatPenggunaId: string;
  JenisEntitas: string;
  TokenSinkronisasi: string | null;
  TerakhirSinkronPada: string | null;
}

export interface LaporanTersimpan {
  Id: string;
  OrganisasiId: string;
  Nama: string;
  Jenis: string;
  Konfigurasi: Record<string, unknown> | unknown[];
  Pribadi: boolean;
  PemilikId: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface DasborTersimpan {
  Id: string;
  OrganisasiId: string;
  Nama: string;
  PemilikId: string | null;
  Bawaan: boolean;
  Konfigurasi: Record<string, unknown> | unknown[] | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface KomponenDasbor {
  Id: string;
  OrganisasiId: string;
  DasborTersimpanId: string;
  JenisKomponen: string;
  Judul: string | null;
  Konfigurasi: Record<string, unknown> | unknown[];
  PosisiX: number;
  PosisiY: number;
  Lebar: number;
  Tinggi: number;
  Urutan: number;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface FiturPaket {
  Id: string;
  Kode: string;
  Nama: string;
  Deskripsi: string | null;
  TipeBatas: string;
  DibuatPada: string;
}

export interface PaketLangganan {
  Id: string;
  Kode: string;
  Nama: string;
  Deskripsi: string | null;
  HargaBulanan: number;
  HargaTahunan: number;
  MataUang: string;
  Aktif: boolean;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface PaketFitur {
  Id: string;
  PaketLanggananId: string;
  FiturPaketId: string;
  Diizinkan: boolean;
  BatasNilai: number | null;
  NilaiJson: Record<string, unknown> | unknown[] | null;
}

export interface Langganan {
  Id: string;
  OrganisasiId: string;
  PaketLanggananId: string;
  Siklus: string;
  MulaiPada: string;
  BerakhirPada: string | null;
  UjiCobaSampai: string | null;
  Status: string;
  BatalPada: string | null;
  DibuatPada: string;
  DiperbaruiPada: string;
}

export interface TagihanLangganan {
  Id: string;
  OrganisasiId: string;
  LanggananId: string;
  Nomor: string;
  PeriodeMulai: string;
  PeriodeSelesai: string;
  JatuhTempo: string;
  Subtotal: number;
  Pajak: number;
  Total: number;
  Status: string;
  DibuatPada: string;
}

export interface PembayaranLangganan {
  Id: string;
  OrganisasiId: string;
  TagihanLanggananId: string;
  PenyediaPembayaran: string | null;
  ReferensiEksternal: string | null;
  Metode: string | null;
  Jumlah: number;
  Status: string;
  DibayarPada: string | null;
  MuatanData: Record<string, unknown> | unknown[] | null;
  DibuatPada: string;
}

export type NamaViewAmanpoll =
  'ViewRingkasanAset' | 'ViewStokSukuCadang' | 'ViewKinerjaPerintahKerja' | 'ViewKepatuhanKalibrasi';
