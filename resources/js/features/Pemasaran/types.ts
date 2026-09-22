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
