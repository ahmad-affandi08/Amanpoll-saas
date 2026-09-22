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

/*
 * Ditulis sebagai type alias, bukan interface, supaya dapat dikirim apa adanya
 * lewat Inertia: hanya type alias yang dianggap cocok dengan indeks
 * FormDataConvertible tanpa harus menambahkan indeks palsu ke bentuknya.
 */
export type BlokEditor = {
  Jenis: string;
  /** Isi blok bebas bentuk; editor menyuntingnya sebagai JSON. */
  Isi: Record<string, FormDataConvertible>;
  FormulirKode: string | null;
};

/**
 * Blok di dalam editor. `Kunci` hanya hidup di browser: ia menjaga identitas
 * satu blok saat daftarnya diurutkan ulang, supaya isi yang sedang diketik
 * tidak berpindah kartu. Server tidak mengenalnya dan membuangnya saat
 * divalidasi.
 */
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
