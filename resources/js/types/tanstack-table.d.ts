import '@tanstack/react-table';

declare module '@tanstack/react-table' {
  /**
   * Metadata kolom Amanpoll.
   *
   * `label` sudah dipakai luas oleh pemilih kolom; `kartu` dan `labelKartu`
   * ditambahkan untuk tampilan kartu di layar sempit (DESIGN.md 9.3). Peran
   * kolom ditaruh di sini supaya definisi kolom tetap satu-satunya tempat yang
   * tahu arti kolomnya, dan komponen kartu tidak perlu menebak.
   */
  interface ColumnMeta<TData extends RowData, TValue> {
    /** Nama kolom yang terbaca manusia, dipakai pemilih tampilan kolom. */
    label?: string;
    kartu?: 'judul' | 'rincian' | 'aksi' | 'sembunyi';
    /** Label pada kartu; header kolom sering berupa komponen pengurut, bukan teks. */
    labelKartu?: string;
  }
}
