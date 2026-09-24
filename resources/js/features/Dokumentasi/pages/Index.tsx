import type { ComponentType } from 'react';
import { Head } from '@inertiajs/react';
import {
  KerangkaDokumentasi,
  type ButirDaftarIsi,
} from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import { semuaHalaman } from '@/features/Dokumentasi/daftar-halaman';
import { Aset, daftarIsi as isiAset } from '@/features/Dokumentasi/components/isi/Aset';
import { Kalibrasi, daftarIsi as isiKalibrasi } from '@/features/Dokumentasi/components/isi/Kalibrasi';
import { Keluhan, daftarIsi as isiKeluhan } from '@/features/Dokumentasi/components/isi/Keluhan';
import { Lanjutan, daftarIsi as isiLanjutan } from '@/features/Dokumentasi/components/isi/Lanjutan';
import { MasterAset, daftarIsi as isiMasterAset } from '@/features/Dokumentasi/components/isi/MasterAset';
import { MutasiStok, daftarIsi as isiMutasiStok } from '@/features/Dokumentasi/components/isi/MutasiStok';
import { Organisasi, daftarIsi as isiOrganisasi } from '@/features/Dokumentasi/components/isi/Organisasi';
import { Pengadaan, daftarIsi as isiPengadaan } from '@/features/Dokumentasi/components/isi/Pengadaan';
import { Pengenalan, daftarIsi as isiPengenalan } from '@/features/Dokumentasi/components/isi/Pengenalan';
import { Pengguna, daftarIsi as isiPengguna } from '@/features/Dokumentasi/components/isi/Pengguna';
import { Penomoran, daftarIsi as isiPenomoran } from '@/features/Dokumentasi/components/isi/Penomoran';
import {
  PerintahKerja,
  daftarIsi as isiPerintahKerja,
} from '@/features/Dokumentasi/components/isi/PerintahKerja';
import { Persediaan, daftarIsi as isiPersediaan } from '@/features/Dokumentasi/components/isi/Persediaan';
import { Persetujuan, daftarIsi as isiPersetujuan } from '@/features/Dokumentasi/components/isi/Persetujuan';
import { Persiapan, daftarIsi as isiPersiapan } from '@/features/Dokumentasi/components/isi/Persiapan';
import { Preventif, daftarIsi as isiPreventif } from '@/features/Dokumentasi/components/isi/Preventif';
import { SiklusAset, daftarIsi as isiSiklusAset } from '@/features/Dokumentasi/components/isi/SiklusAset';
import {
  UnitPengelola,
  daftarIsi as isiUnitPengelola,
} from '@/features/Dokumentasi/components/isi/UnitPengelola';

interface Props {
  halaman: string;
}

/** Slug di sini harus sama persis dengan yang divalidasi DokumentasiController. */
const isiPer: Record<string, { daftarIsi: ButirDaftarIsi[]; Komponen: ComponentType }> = {
  pengenalan: { daftarIsi: isiPengenalan, Komponen: Pengenalan },
  persiapan: { daftarIsi: isiPersiapan, Komponen: Persiapan },
  organisasi: { daftarIsi: isiOrganisasi, Komponen: Organisasi },
  pengguna: { daftarIsi: isiPengguna, Komponen: Pengguna },
  penomoran: { daftarIsi: isiPenomoran, Komponen: Penomoran },
  'master-aset': { daftarIsi: isiMasterAset, Komponen: MasterAset },
  aset: { daftarIsi: isiAset, Komponen: Aset },
  'siklus-aset': { daftarIsi: isiSiklusAset, Komponen: SiklusAset },
  keluhan: { daftarIsi: isiKeluhan, Komponen: Keluhan },
  'perintah-kerja': { daftarIsi: isiPerintahKerja, Komponen: PerintahKerja },
  preventif: { daftarIsi: isiPreventif, Komponen: Preventif },
  kalibrasi: { daftarIsi: isiKalibrasi, Komponen: Kalibrasi },
  persediaan: { daftarIsi: isiPersediaan, Komponen: Persediaan },
  'mutasi-stok': { daftarIsi: isiMutasiStok, Komponen: MutasiStok },
  pengadaan: { daftarIsi: isiPengadaan, Komponen: Pengadaan },
  'unit-pengelola': { daftarIsi: isiUnitPengelola, Komponen: UnitPengelola },
  persetujuan: { daftarIsi: isiPersetujuan, Komponen: Persetujuan },
  lanjutan: { daftarIsi: isiLanjutan, Komponen: Lanjutan },
};

export default function DokumentasiIndex({ halaman }: Props) {
  const berkas = semuaHalaman.find((satu) => satu.slug === halaman) ?? semuaHalaman[0];
  const isi = isiPer[berkas.slug];

  if (!isi) {
    return null;
  }

  const { Komponen } = isi;

  return (
    <>
      <Head title={`${berkas.judul} · Dokumentasi`} />
      <KerangkaDokumentasi
        slug={berkas.slug}
        judul={berkas.judul}
        ringkas={berkas.ringkas}
        daftarIsi={isi.daftarIsi}
      >
        <Komponen />
      </KerangkaDokumentasi>
    </>
  );
}
