export interface AlkesAspak {
  Id: string;
  Kode: string;
  Nama: string;
  Kelompok: string | null;
  Satuan: string | null;
  Aktif: boolean;
  JumlahPemetaan: number;
}

export interface PemetaanAspak {
  Id: string;
  AlkesAspakId: string;
  KodeAlkes: string | null;
  NamaAlkes: string | null;
  KategoriAsetId: string | null;
  NamaKategoriAset: string | null;
  ModelAsetId: string | null;
  NamaModelAset: string | null;
}

export interface RingkasanAspak {
  JumlahAlkes: number;
  JumlahPemetaan: number;
  AsetTerpetakan: number;
  AsetBelumTerpetakan: number;
  LokasiTanpaKodeRuang: number;
}
