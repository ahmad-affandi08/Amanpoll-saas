export interface KodeBarang {
  Id: string;
  Kode: string;
  Uraian: string;
  JumlahAset: number;
}

export interface PilihanStandar {
  nilai: string;
  label: string;
}

export interface RingkasanKodefikasi {
  JumlahKode: number;
  AsetBerkode: number;
  AsetBelumBerkode: number;
}

export interface PenetapanKodeAset {
  Id: string;
  Standar: string;
  LabelStandar: string;
  Kode: string;
  Uraian: string;
  Nup: number;
  KodeRegistrasi: string | null;
  AlasanBelumLengkap: string | null;
}
