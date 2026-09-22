import type { FormDataConvertible } from '@inertiajs/core';

export interface TahapRingkas {
  Kode: string;
  Nama: string;
  TahapAkhir: boolean;
}

export interface Prospek {
  Id: string;
  Nama: string;
  Email: string | null;
  Perusahaan: string | null;
  Industri: string | null;
  Sumber: string;
  Kampanye: string | null;
  Tahap: string | null;
  KodeTahap: string | null;
  Skor: number;
  AktivitasTerakhirPada: string | null;
  DibuatPada: string;
}

export interface ProspekDetail extends Prospek {
  Telepon: string | null;
  WhatsApp: string | null;
  Jabatan: string | null;
  Catatan: string | null;
  Tag: string[];
  RincianSkor: Array<{ Peristiwa: string; Bobot: number }>;
  Qualified: boolean;
}

/** Satu perpindahan tahap beserta lama menetap di tahap tujuannya. */
export interface EntriRiwayatTahap {
  Id: string;
  TahapSebelum: string | null;
  TahapSesudah: string | null;
  Alasan: string | null;
  BerpindahPada: string;
  LamaHari: number;
  Berjalan: boolean;
}

export interface EntriTimeline {
  Sumber: 'Peristiwa' | 'Aktivitas';
  Jenis: string;
  Judul: string;
  Isi: string | null;
  Pada: string;
}

/* Ditulis sebagai type alias, bukan interface, supaya dapat dikirim apa adanya lewat Inertia. */
export type BlokEditor = {
  Jenis: string;
  /** Isi blok bebas bentuk; editor menyuntingnya sebagai JSON. */
  Isi: Record<string, FormDataConvertible>;
  FormulirKode: string | null;
};

/** Blok di dalam editor. */
export type BlokDisunting = BlokEditor & {
  Kunci: string;
};

export interface HalamanRingkas {
  Id: string;
  Slug: string;
  Tipe: string;
  Judul: string;
  Status: string;
  Segmen: string | null;
  KampanyeId: string | null;
  Kampanye: string | null;
  NoIndex: boolean;
  TerbitPada: string | null;
  TarikPada: string | null;
  VersiTerbitNomor: number | null;
  VersiDrafNomor: number | null;
  VersiDrafId: string | null;
  UrlPublik: string | null;
}

export interface HalamanDetail extends HalamanRingkas {
  MetaJudul: string | null;
  MetaDeskripsi: string | null;
  Kanonik: string | null;
  OgJudul: string | null;
  OgDeskripsi: string | null;
  OgGambar: string | null;
  SkemaTipe: string | null;
  Blok: BlokEditor[];
}

export interface VersiHalaman {
  Id: string;
  Nomor: number;
  Judul: string;
  Catatan: string | null;
  DibuatPada: string;
  Terbit: boolean;
  Draf: boolean;
}

export interface PilihanHalaman {
  Tipe: string[];
  Status: string[];
  Blok: string[];
  Segmen: Record<string, string>;
  Formulir: Array<{ Kode: string; Nama: string }>;
  Kampanye: Array<{ Id: string; Nama: string }>;
  /** Kode paket yang boleh disebut blok harga; angkanya milik domain Langganan. */
  Paket: Array<{ Kode: string; Nama: string }>;
  SiklusHarga: string[];
  FiturPaket: string[];
}

/** Type alias, dengan alasan yang sama seperti BlokEditor. */
export type FieldFormulir = {
  Kode: string;
  Label: string;
  Jenis: string;
  Wajib: boolean;
  Pilihan: string[];
  Placeholder: string | null;
  Bantuan: string | null;
};

export interface Formulir {
  Id: string;
  Kode: string;
  Nama: string;
  PesanSukses: string | null;
  UrlRedirect: string | null;
  Sumber: string;
  KampanyeId: string | null;
  Tag: string[];
  PemicuOtomasi: string | null;
  UrlWebhook: string | null;
  WajibPersetujuan: boolean;
  CaptchaAktif: boolean;
  Aktif: boolean;
  JumlahPengiriman: number;
  Field: FieldFormulir[];
}

export interface PilihanFormulir {
  Jenis: string[];
  Sumber: string[];
  Kampanye: Array<{ Id: string; Nama: string }>;
}

export interface PengirimanFormulir {
  Id: string;
  Data: Record<string, unknown>;
  Persetujuan: boolean;
  ProspekId: string | null;
  ProspekNama: string | null;
  DikirimPada: string;
}

export interface Redirect {
  Id: string;
  Dari: string;
  Ke: string | null;
  Kode: string;
  Aktif: boolean;
  Catatan: string | null;
  JumlahDipakai: number;
  TerakhirDipakaiPada: string | null;
}

export interface AturanSkor {
  Id: string;
  Peristiwa: string;
  Bobot: number;
  Aktif: boolean;
  Keterangan: string | null;
  /** Peristiwa, Turunan, atau Tertunda — lihat KatalogPeristiwaSkor. */
  Asal: string | null;
  /** Aktif dan sinyalnya punya penghasil. */
  Berlaku: boolean;
  JumlahDipakai: number;
}

export interface PilihanAturanSkor {
  /** kode sinyal => asalnya. */
  Peristiwa: Record<string, string>;
  AsalTertunda: string;
}

export interface Trial {
  Id: string;
  Organisasi: string | null;
  OrganisasiId: string;
  Prospek: string | null;
  ProspekId: string | null;
  Status: string;
  MulaiPada: string;
  BerakhirPada: string;
  TeraktivasiPada: string | null;
  KonversiPada: string | null;
  HariPerpanjangan: number;
  ButirSelesai: string[];
  StatusBerikutnya: string[];
}

export interface KonfigurasiTrial {
  DurasiHari: number;
  PaketId: string | null;
  NamaPaket: string | null;
  KartuDiperlukan: boolean;
  BatasPengguna: number | null;
  BatasLokasi: number | null;
  BatasAset: number | null;
  HariTenggang: number;
  PerpanjanganMaksHari: number;
}

export interface PilihanTrial {
  Status: string[];
  Butir: Array<{ Kode: string; Label: string }>;
  ButirWajib: string[];
}

export interface TemplateEmail {
  Id: string;
  Kode: string;
  Nama: string;
  Jenis: string;
  Subjek: string;
  IsiHtml: string;
  IsiTeks: string | null;
  Aktif: boolean;
}

export interface LangkahSequence {
  Id: string;
  TemplateEmailPemasaranId: string;
  TemplateNama: string;
  Urutan: number;
  HariKe: number;
  Aktif: boolean;
}

export interface SequenceEmail {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  Aktif: boolean;
  JumlahBerjalan: number;
  Langkah: LangkahSequence[];
}

export interface BarisSupresi {
  Id: string;
  Email: string | null;
  Alasan: string;
  Catatan: string | null;
  DitambahkanPada: string | null;
}

export interface RiwayatKonsen {
  Id: string;
  Email: string;
  Diberikan: boolean;
  Sumber: string;
  VersiKebijakan: string;
  DicatatPada: string | null;
}

export interface PermintaanData {
  Id: string;
  Email: string | null;
  Jenis: string;
  Catatan: string | null;
  DimintaPada: string | null;
  DiprosesPada: string | null;
}

export interface RingkasanOtomasi {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  Pemicu: string;
  PemicuBerlaku: boolean;
  Aktif: boolean;
  NomorVersiAktif: number | null;
  JumlahBerjalan: number;
  JumlahDlq: number;
}

export interface LangkahOtomasi {
  Id: string;
  Urutan: number;
  Jenis: string;
  Konfigurasi: Record<string, unknown>;
}

export interface VersiOtomasi {
  Id: string;
  Nomor: number;
  Status: string;
  DiterbitkanPada: string | null;
  Langkah: LangkahOtomasi[];
}

export interface LogOtomasi {
  Urutan: number;
  Jenis: string;
  Hasil: string;
  Ringkasan: string | null;
}

export interface EksekusiOtomasi {
  Id: string;
  Prospek: string | null;
  Status: string;
  LangkahBerikutnya: number;
  Percobaan: number;
  LanjutPada: string | null;
  Galat: string | null;
  DimulaiPada: string;
  Log: LogOtomasi[];
}

export interface DetailOtomasi {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  Pemicu: string;
  PemicuBerlaku: boolean;
  Aktif: boolean;
  VersiAktifId: string | null;
}

export interface KodeReferralRingkas {
  Id: string;
  Kode: string;
  Organisasi: string;
  Url: string | null;
  Aktif: boolean;
}

export interface ProgramReferral {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  JenisReward: string;
  NilaiReward: number;
  HariKedaluwarsa: number;
  Aktif: boolean;
  JumlahReferral: number;
  Kodenya: KodeReferralRingkas[];
}

export interface RewardReferralRingkas {
  Id: string;
  Penerima: string;
  Jenis: string;
  Nilai: number;
  Status: string;
  Percobaan: number;
  Ringkasan: string | null;
  Galat: string | null;
  DibuatPada: string;
  DiberikanPada: string | null;
}

export interface KeywordTertaut {
  Id: string;
  Keyword: string;
  Utama: boolean;
}

export interface KontenPemasaran {
  Id: string;
  Slug: string;
  Ruas: string;
  Jenis: string;
  Judul: string;
  Status: string;
  PenulisNama: string | null;
  KampanyeId: string | null;
  Kampanye: string | null;
  NoIndex: boolean;
  DiSitemap: boolean;
  TerbitPada: string | null;
  VersiTerbitNomor: number | null;
  VersiDrafNomor: number | null;
  VersiDrafId: string | null;
  UrlPublik: string | null;
  Keyword: KeywordTertaut[];
  Ringkasan?: string | null;
  IsiMarkdown?: string;
  MetaJudul?: string | null;
  MetaDeskripsi?: string | null;
  Kanonik?: string | null;
  OgJudul?: string | null;
  OgDeskripsi?: string | null;
  OgGambar?: string | null;
  SkemaTipe?: string | null;
}

export interface VersiKonten {
  Id: string;
  Nomor: number;
  Judul: string;
  Catatan: string | null;
  DibuatPada: string;
  Terbit: boolean;
  Draf: boolean;
}

export interface KeywordSeo {
  Id: string;
  Keyword: string;
  ClusterSeoId: string | null;
  Cluster: string | null;
  Intent: string;
  IntentLabel: string;
  TargetUrl: string | null;
  Prioritas: string;
  Urutan: number;
  Status: string;
  Catatan: string | null;
}

export interface ClusterSeo {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
}

export interface PilihanKonten {
  Jenis: string[];
  AwalanJalur: Record<string, string>;
  Status: string[];
  Intent: Record<string, string>;
  Prioritas: string[];
  StatusKeyword: string[];
  Kampanye: Record<string, string>;
}

export interface DistribusiSosial {
  Id: string;
  Channel: string;
  Caption: string;
  MediaUrl: string | null;
  Cta: string | null;
  TautanTujuan: string | null;
  UtmSource: string | null;
  UtmMedium: string | null;
  UtmTerm: string | null;
  UtmContent: string | null;
  Status: string;
  TujuanStatus: string[];
  TautanBerUtm: string | null;
  UrlTerbit: string | null;
  Galat: string | null;
  Percobaan: number;
  TerbitPada: string | null;
  JadwalPada: string | null;
}

export interface KontenSosial {
  Id: string;
  Kode: string;
  Judul: string;
  Ringkasan: string | null;
  MediaUrl: string | null;
  HalamanId: string | null;
  KampanyeId: string | null;
  KampanyeKode: string | null;
  HalamanSlug: string | null;
  Distribusi: DistribusiSosial[];
}

export interface PilihanSosial {
  Channel: string[];
  ChannelWajibMedia: string[];
  Kampanye: Record<string, string>;
  Halaman: Record<string, string>;
}

export interface TemplateWhatsApp {
  Id: string;
  Kode: string;
  Nama: string;
  Bahasa: string;
  Kategori: string;
  IsiTeks: string;
  StatusPersetujuan: string;
  AlasanPenolakan: string | null;
  IdTemplatePenyedia: string | null;
  DiperiksaPada: string | null;
  Aktif: boolean;
  SiapKirim: boolean;
  TujuanStatus: string[];
}

export interface MenuWhatsApp {
  Id: string;
  Kunci: string;
  Urutan: number;
  Label: string;
  Balasan: string;
  Aktif: boolean;
}

export interface PilihanKampanye {
  Status: string[];
  Objective: string[];
  Channel: string[];
  Metrik: string[];
  JenisKonten: string[];
  Halaman: Record<string, string>;
  Formulir: Record<string, string>;
}

export interface Kampanye {
  Id: string;
  Kode: string;
  Nama: string;
  Objective: string;
  Status: string;
  Budget: number | null;
  Audience: string | null;
  Offer: string | null;
  HalamanId: string | null;
  FormulirId: string | null;
  UtmSource: string | null;
  UtmMedium: string | null;
  UtmTerm: string | null;
  UtmContent: string | null;
  MulaiPada: string | null;
  SelesaiPada: string | null;
  Catatan: string | null;
  Channel: string[];
  TujuanStatus: string[];
}

export interface BiayaKampanye {
  Id: string;
  Channel: string;
  Tanggal: string;
  Jumlah: number;
  Catatan: string | null;
}

export interface TargetKampanye {
  Id: string;
  Metrik: string;
  Nilai: number;
  SatuanUang: boolean;
  Realisasi: number;
}

export interface KontenKampanye {
  Id: string;
  Jenis: string;
  Judul: string;
  Tautan: string | null;
  Catatan: string | null;
  Urutan: number;
}

export interface PilihanGrowth {
  Channel: string[];
  Kampanye: string[];
  Industri: string[];
  Perangkat: string[];
  Paket: string[];
  Referral: string[];
  AlertBelumTersedia: string[];
}

/** Nilai filter dashboard growth, seluruhnya dari query string. */
export type FilterGrowth = Record<string, string | null>;

export interface ModelPerbandingan {
  Model: string;
  Label: string;
  PerChannel: Record<string, number>;
}

export interface Attribution {
  Model: string;
  Label: string;
  Keterangan: string;
  ParuhHari: number;
  Perbandingan: ModelPerbandingan[];
}

export interface TahapFunnel {
  Tahap: string;
  Jumlah: number;
  Sumber: string;
  PersenDariSebelumnya: number | null;
}

export interface KpiGrowth {
  Kunci: string;
  Nama: string;
  Kelompok: string;
  LabelKelompok: string;
  Satuan: string;
  Desimal: number;
  Formula: string;
  Sumber: string;
  Tersedia: boolean;
  BelumTersedia: string | null;
  Nilai: number | null;
}

export interface BarisKampanye {
  KampanyeId: string;
  Kode: string;
  Nama: string;
  Visitor: number;
  Lead: number;
  Trial: number;
  Bayar: number;
  Revenue: number;
  Biaya: number;
}

export interface BarisCac {
  Channel: string;
  Biaya: number;
  Pelanggan: number;
  Cac: number | null;
  Alasan: string | null;
}

export interface CacTakTerpecah {
  Biaya: number;
  Pelanggan: number;
  Kampanye: string[];
}

export interface BarisHalaman {
  Landing: string;
  Pengunjung: number;
  Lead: number;
  Konversi: number;
}

export interface AlertGrowth {
  Id: string;
  Kode: string;
  Tingkat: string;
  Judul: string;
  Isi: string;
  DibuatPada: string;
}
