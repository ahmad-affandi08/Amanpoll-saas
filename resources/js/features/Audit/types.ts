export interface CatatanAudit {
  Id: string;
  PenggunaId: string | null;
  NamaPengguna: string | null;
  Aksi: string;
  JenisEntitas: string;
  EntitasId: string | null;
  DataSebelum: Record<string, unknown> | null;
  DataSesudah: Record<string, unknown> | null;
  AlamatIp: string | null;
  AgenPengguna: string | null;
  KorelasiId: string | null;
  DibuatPada: string;
}

export interface FilterCatatanAudit {
  jenisEntitas?: string;
  entitasId?: string;
  penggunaId?: string;
  aksi?: string;
  dariTanggal?: string;
  sampaiTanggal?: string;
}
