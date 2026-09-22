/** Bentuk data konsol partner, dipakai halaman dan seluruh dialognya. */

export type Program = {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  HariAtribusi: number;
  Aktif: boolean;
  JumlahPartner: number;
};

export type Partner = {
  Id: string;
  Kode: string;
  NamaPerusahaan: string;
  Jenis: string;
  LabelJenis: string;
  NamaPic: string;
  EmailPic: string;
  TeleponPic: string | null;
  Status: string;
  Program: string | null;
  ProgramPartnerId: string;
  ReferensiPerjanjian: string | null;
  ReferensiPayout: string | null;
  TerakhirMasukPada: string | null;
};

export type Aturan = {
  Id: string;
  Nama: string;
  Program: string | null;
  ProgramPartnerId: string;
  PartnerId: string | null;
  Partner: string | null;
  Jenis: string;
  LabelJenis: string;
  Nilai: number;
  MaksPembayaran: number | null;
  Aktif: boolean;
};

export type Lead = {
  Id: string;
  Partner: string;
  NamaPerusahaan: string;
  NamaKontak: string;
  Email: string;
  Telepon: string | null;
  Catatan: string | null;
  Status: string;
  AlasanDitolak: string | null;
  DikirimPada: string;
};

export type Komisi = {
  Id: string;
  Partner: string;
  Lead: string;
  JumlahPembayaran: number;
  Jumlah: number;
  Status: string;
  PayoutPartnerId: string | null;
  DibuatPada: string;
};

export type Payout = {
  Id: string;
  Partner: string;
  Nomor: string;
  Jumlah: number;
  JumlahKomisi: number;
  Status: string;
  ReferensiPembayaran: string | null;
  Catatan: string | null;
  DibayarPada: string | null;
};

export type Pilihan = { Jenis: string[]; StatusPartner: string[]; JenisKomisi: string[] };
