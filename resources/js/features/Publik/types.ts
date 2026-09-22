export interface FieldPublik {
  Kode: string;
  Label: string;
  Jenis: string;
  Wajib: boolean;
  Pilihan: string[];
  Placeholder: string | null;
  Bantuan: string | null;
}

/** Hanya terkirim saat CAPTCHA formulir menyala. */
export interface KonfigurasiCaptcha {
  KunciSitus: string | null;
  Skrip: string | null;
  NamaField: string;
}

export interface FormulirPublik {
  Kode: string;
  Nama: string;
  WajibPersetujuan: boolean;
  CaptchaAktif: boolean;
  Captcha: KonfigurasiCaptcha | null;
  Field: FieldPublik[];
}

/** Isi blok bebas bentuk: tiap jenis blok membaca kunci yang dipahaminya. */
export interface BlokHalaman {
  Id: string;
  Jenis: string;
  Isi: Record<string, unknown>;
  Formulir: FormulirPublik | null;
}

export interface MetaHalaman {
  Judul: string;
  Deskripsi: string | null;
  Kanonik: string | null;
  OgJudul: string | null;
  OgDeskripsi: string | null;
  OgGambar: string | null;
  SkemaTipe: string | null;
}

export interface IsiHalaman {
  Slug: string;
  Tipe: string;
  Judul: string;
  Segmen: string | null;
  NoIndex: boolean;
  VersiNomor: number;
  Meta: MetaHalaman;
  Blok: BlokHalaman[];
  /** Hanya terisi pada pratinjau draf. */
  Pratinjau?: boolean;
}

export interface IsiKonten {
  Slug: string;
  Jenis: string;
  Judul: string;
  Ringkasan: string | null;
  IsiMarkdown: string;
  PenulisNama: string | null;
  NoIndex: boolean;
  VersiNomor: number;
  TerbitPada: string | null;
  Meta: MetaHalaman;
  /** Hanya terisi pada pratinjau draf. */
  Pratinjau?: boolean;
}

export interface PropsPublik {
  kanonik: string | null;
  urlMasuk: string;
  urlDaftar: string;
}

export interface ToolPublik {
  Kode: string;
  Judul: string;
  Jalur: string;
  MetrikSorotan: string | null;
  FieldPerangkap: string;
}

export interface HasilKeandalan {
  MenitOperasional: number;
  TotalJam: number;
  Mttr: number | null;
  Mtbf: number | null;
  Ketersediaan: number | null;
  AlasanMttr: string | null;
  AlasanMtbf: string | null;
  AlasanKetersediaan: string | null;
  Peringatan: string | null;
}

export interface LabelQr {
  Kode: string;
  Svg: string;
}
