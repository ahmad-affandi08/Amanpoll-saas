export interface PenggunaAuth {
  Id: string;
  Nama: string;
  Email: string;
  OrganisasiId: string;
}

export interface PageProps {
  namaAplikasi: string;
  auth: { pengguna: PenggunaAuth | null };
  flash: { sukses?: string | null; gagal?: string | null };
  [key: string]: unknown;
}
