/**
 * Peta isi dokumentasi.
 *
 * Urutannya adalah urutan pengerjaan, bukan urutan abjad: pembaca yang
 * mengikutinya dari atas ke bawah tidak akan menemui langkah yang bergantung
 * pada sesuatu yang belum ia buat.
 */
export interface HalamanDokumentasi {
  slug: string;
  judul: string;
  ringkas: string;
}

export interface GrupDokumentasi {
  label: string;
  halaman: HalamanDokumentasi[];
}

export const grupDokumentasi: GrupDokumentasi[] = [
  {
    label: 'Mulai',
    halaman: [
      {
        slug: 'pengenalan',
        judul: 'Pengenalan',
        ringkas: 'Apa yang dikerjakan Amanpoll dan bagaimana modulnya saling bergantung.',
      },
      {
        slug: 'persiapan',
        judul: 'Urutan Persiapan',
        ringkas: 'Daftar periksa dari akun kosong sampai siap dipakai operasional.',
      },
    ],
  },
  {
    label: 'Fondasi',
    halaman: [
      {
        slug: 'organisasi',
        judul: 'Organisasi & Lokasi',
        ringkas: 'Unit organisasi, lokasi fisik, dan zona waktu yang dipakai seluruh perhitungan.',
      },
      {
        slug: 'pengguna',
        judul: 'Pengguna, Peran & Izin',
        ringkas: 'Membuat peran, menetapkan izin, dan menambahkan pengguna.',
      },
      {
        slug: 'penomoran',
        judul: 'Penomoran & Hari Libur',
        ringkas: 'Pola nomor dokumen yang wajib ada sebelum transaksi, kode otomatis, dan kalender libur.',
      },
    ],
  },
  {
    label: 'Aset',
    halaman: [
      {
        slug: 'master-aset',
        judul: 'Kategori, Merek & Model',
        ringkas: 'Data induk yang menentukan perilaku aset di bawahnya.',
      },
      {
        slug: 'aset',
        judul: 'Mendata Aset',
        ringkas: 'Membuat aset, kode QR, tag, dan kolom kustom.',
      },
      {
        slug: 'siklus-aset',
        judul: 'Mutasi & Penghapusan',
        ringkas: 'Memindahkan, menyerahterimakan, dan menghapus aset beserta jejaknya.',
      },
    ],
  },
  {
    label: 'Pemeliharaan',
    halaman: [
      {
        slug: 'keluhan',
        judul: 'Keluhan & SLA',
        ringkas: 'Kategori keluhan, tingkat layanan, dan jalannya satu keluhan.',
      },
      {
        slug: 'perintah-kerja',
        judul: 'Perintah Kerja',
        ringkas: 'Menugaskan pekerjaan, mencatat suku cadang terpakai, dan menutupnya.',
      },
      {
        slug: 'preventif',
        judul: 'Preventif & Inspeksi',
        ringkas: 'Rencana berkala, daftar periksa, dan inspeksi.',
      },
      {
        slug: 'kalibrasi',
        judul: 'Kalibrasi',
        ringkas: 'Jenis, rencana, pelaksanaan, dan sertifikat kalibrasi.',
      },
    ],
  },
  {
    label: 'Persediaan & Pengadaan',
    halaman: [
      {
        slug: 'persediaan',
        judul: 'Gudang & Suku Cadang',
        ringkas: 'Gudang, lokasi rak, suku cadang, dan saldo stok.',
      },
      {
        slug: 'mutasi-stok',
        judul: 'Mutasi Stok & Reservasi',
        ringkas: 'Satu-satunya cara saldo stok berubah.',
      },
      {
        slug: 'pengadaan',
        judul: 'Penyedia & Pengadaan',
        ringkas: 'Dari anggaran dan usulan sampai penerimaan barang dan tagihan.',
      },
    ],
  },
  {
    label: 'Lanjutan',
    halaman: [
      {
        slug: 'unit-pengelola',
        judul: 'Beberapa Unit Pengelola',
        ringkas: 'Memisahkan antrian, teknisi, gudang, dan stok per bagian, mis. IPSRS dan IT.',
      },
      {
        slug: 'persetujuan',
        judul: 'Alur Persetujuan',
        ringkas: 'Menyusun tahap persetujuan dan membacanya saat berjalan.',
      },
      {
        slug: 'lanjutan',
        judul: 'Laporan, Integrasi & Offline',
        ringkas: 'Laporan tersimpan, kunci API, webhook, dan mode teknisi.',
      },
    ],
  },
];

export const semuaHalaman: HalamanDokumentasi[] = grupDokumentasi.flatMap((grup) => grup.halaman);

export function halamanKe(slug: string): number {
  return semuaHalaman.findIndex((satu) => satu.slug === slug);
}
