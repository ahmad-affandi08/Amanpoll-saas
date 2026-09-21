import type { PermintaanPembelian } from '@/features/PermintaanPembelian/types';

export type StatusPermintaanPenawaran = 'Draft' | 'Dibuka' | 'Ditutup';

export type StatusPenawaranPenyedia = 'Diajukan' | 'Terpilih' | 'Ditolak';

export interface PenyediaDiundang {
  Id: string;
  PenyediaId: string;
  NamaPenyedia: string | null;
  Status: string;
  DikirimPada: string | null;
  DilihatPada: string | null;
}

export interface DetailPenawaranPenyedia {
  Id: string;
  DetailPermintaanPembelianId: string | null;
  Deskripsi: string;
  Jumlah: string;
  HargaSatuan: string;
  Diskon: string;
  Pajak: string;
  Total: string;
  WaktuPengirimanHari: number | null;
}

export interface PenawaranPenyedia {
  Id: string;
  PermintaanPenawaranId: string;
  PenyediaId: string;
  NamaPenyedia?: string | null;
  NomorPenawaran: string | null;
  TanggalPenawaran: string;
  BerlakuSampai: string | null;
  MataUang: string;
  Subtotal: string;
  Pajak: string;
  Diskon: string;
  Total: string;
  Status: StatusPenawaranPenyedia;
  Catatan: string | null;
  Detail?: DetailPenawaranPenyedia[];
  PermintaanPenawaran?: PermintaanPenawaran;
  DibuatPada: string;
}

export interface PermintaanPenawaran {
  Id: string;
  Nomor: string;
  PermintaanPembelianId: string | null;
  PermintaanPembelian?: PermintaanPembelian;
  TanggalDibuka: string;
  BatasPenawaran: string | null;
  Status: StatusPermintaanPenawaran;
  Catatan: string | null;
  NamaPembuat?: string | null;
  JumlahPenyediaDiundang?: number;
  JumlahPenawaran?: number;
  PenyediaDiundang?: PenyediaDiundang[];
  Penawaran?: PenawaranPenyedia[];
  DibuatPada: string;
}
