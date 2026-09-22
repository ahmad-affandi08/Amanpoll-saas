export interface PenggunaAuth {
  Id: string;
  Nama: string;
  Email: string;
  OrganisasiId: string;
  AvatarUrl?: string | null;
  Jabatan?: string | null;
  organisasi?: {
    Id: string;
    Nama: string;
    Kode: string;
  } | null;
}

export interface PageProps {
  namaAplikasi: string;
  auth: { pengguna: PenggunaAuth | null };
  izin: string[];
  flash: {
    sukses?: string | null;
    gagal?: string | null;
    tokenKunciApi?: string | null;
    instruksiPembayaran?: unknown;
  };
  entitlement: {
    Fitur?: Record<string, boolean>;
    Batas?: Record<string, number | null>;
    AksesPenuh?: boolean;
    [kunci: string]: unknown;
  };
  [key: string]: unknown;
}

/** Dipakai untuk tabel yang tumbuh tak terbatas. */
export interface Paginasi<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
  };
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
}
