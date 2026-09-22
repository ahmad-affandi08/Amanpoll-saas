import '@tanstack/react-table';

declare module '@tanstack/react-table' {
  /** Metadata kolom Amanpoll. */
  interface ColumnMeta<TData extends RowData, TValue> {
    /** Nama kolom yang terbaca manusia, dipakai pemilih tampilan kolom. */
    label?: string;
    kartu?: 'judul' | 'rincian' | 'aksi' | 'sembunyi';
    /** Label pada kartu; header kolom sering berupa komponen pengurut, bukan teks. */
    labelKartu?: string;
  }
}
