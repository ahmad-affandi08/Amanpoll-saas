import type { JenisItemPengadaan } from '@/features/PermintaanPembelian/types';
import type { PenerimaanPembelian } from '@/features/PenerimaanPembelian/types';
import type { TagihanPenyedia } from '@/features/TagihanPenyedia/types';

export type StatusPesananPembelian =
  | 'Draft'
  | 'MenungguPersetujuan'
  | 'Disetujui'
  | 'Ditolak'
  | 'Dikirim'
  | 'DiterimaSebagian'
  | 'DiterimaPenuh';

export interface DetailPesananPembelian {
  Id: string;
  PesananPembelianId: string;
  JenisItem: JenisItemPengadaan;
  SukuCadangId: string | null;
  NamaSukuCadang?: string | null;
  Deskripsi: string;
  Jumlah: string;
  Satuan: string;
  HargaSatuan: string;
  Diskon: string;
  Pajak: string;
  Total: string;
}

export interface PesananPembelian {
  Id: string;
  Nomor: string;
  PenyediaId: string;
  NamaPenyedia?: string | null;
  PermintaanPembelianId: string | null;
  NomorPermintaanPembelian?: string | null;
  PenawaranPenyediaId: string | null;
  PosAnggaranId: string | null;
  NamaPosAnggaran?: string | null;
  TanggalPesanan: string;
  TanggalKirimRencana: string | null;
  MataUang: string;
  Subtotal: string;
  Pajak: string;
  Diskon: string;
  Total: string;
  Status: StatusPesananPembelian;
  Catatan: string | null;
  NamaPembuat?: string | null;
  JumlahPenerimaan?: number;
  Detail?: DetailPesananPembelian[];
  Penerimaan?: PenerimaanPembelian[];
  Tagihan?: TagihanPenyedia[];
  DibuatPada: string;
  DiperbaruiPada: string;
}
