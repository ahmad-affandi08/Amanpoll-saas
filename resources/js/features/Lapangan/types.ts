import type { LapanganBersama, PageProps } from '@/types/global';
import type { Notifikasi } from '@/features/Notifikasi/types';
import type { AntrianServer } from '@/features/Sinkronisasi/types';

/**
 * Prop Inertia bersama `lapangan` (didefinisikan di `@/types/global`, dibagikan `HandleInertiaRequests`):
 * `mode` (Teknisi menang bila memegang keduanya; `null` bukan pengguna lapangan), `murni`
 * (seluruh perannya bertanda Tampilan Lapangan), `bisaBeralih` (pengguna campuran).
 */
export type { LapanganBersama };

/** Mode Lapangan yang ditentukan server dari penanda `TampilanLapangan` pada peran (PRD 8.20). */
export type ModeLapangan = NonNullable<LapanganBersama['mode']>;

/** Props halaman Mode Lapangan. Baca `lapangan` dengan `?.` karena halaman lama mungkin dirender tanpa prop itu. */
export type PropsLapangan = PageProps;

/** Warna chip status (DESIGN.md 36.3). Teks -700 di atas tint -50; `putih` untuk chip di atas hero. */
export type WarnaChip = 'merah' | 'oranye' | 'kuning' | 'biru' | 'hijau' | 'abu' | 'putih';

/** Keadaan satu perhentian (stasiun): sudah dilewati, sedang berlangsung, atau belum. */
export type KeadaanPerhentian = 'lewat' | 'kini' | 'nanti';

/** Kunci tab navigasi bawah. Teknisi: beranda·tugas·pindai·aset·akun; Pelapor: beranda·laporan·lapor·aset·akun. */
export type KunciNavLapangan = 'beranda' | 'tugas' | 'pindai' | 'laporan' | 'lapor' | 'aset' | 'akun';

/** Identitas pengguna untuk layar Akun (dikirim `LapanganAkunController` sebagai prop `akun`). */
export interface AkunLapangan {
  Id: string;
  Nama: string;
  Email: string;
  Telepon: string | null;
  Jabatan: string | null;
  NomorPegawai: string | null;
  AvatarUrl: string | null;
  /** Nama peran yang dipegang, urut abjad. */
  Peran: string[];
}

/** Props halaman Akun Mode Lapangan (`Lapangan/Akun`). Semua opsional: halaman jatuh ke prop bersama `auth`. */
export interface PropsHalamanAkun extends PropsLapangan {
  akun?: AkunLapangan;
  /** Angka ringkas di kartu apung (papan Pelapor layar 15), mis. `[{ Label: 'Laporan dikirim', Nilai: 14 }]`. */
  ringkasan?: { Label: string; Nilai: string | number }[];
}

/** Props halaman Notifikasi Mode Lapangan (`Lapangan/Notifikasi`, dari `LapanganNotifikasiController`). */
export interface PropsHalamanNotifikasi extends PropsLapangan {
  /** 50 notifikasi dalam aplikasi terbaru. */
  notifikasi?: Notifikasi[];
  jumlahBelumDibaca?: number;
}

// Teknisi (C)

/** Lokasi ringkas: nama ruangan dan induknya ("Menara A · Shaft Timur"). */
export interface LokasiRingkasTeknisi {
  Nama: string;
  Induk: string | null;
}

/** Aset ringkas di kartu tiket dan daftar aset. */
export interface AsetRingkasTeknisi {
  Id: string;
  KodeAset: string;
  Nama: string;
  Kategori: string | null;
  Kondisi: string | null;
  Lokasi: LokasiRingkasTeknisi | null;
  /** Thumbnail foto utama (PRD 8.4 "Foto Aset"); `null` = ikon 3D kategori. */
  FotoUtamaThumbnailUrl: string | null;
}

/** Tiket kerja teknisi (`PenyusunLayarTeknisi::ringkas`). */
export interface TiketTeknisi {
  Id: string;
  Nomor: string;
  Judul: string;
  Jenis: string | null;
  Status: string;
  Prioritas: string;
  Versi: number;
  DariKeluhan: boolean;
  DilaporkanPada: string | null;
  DijadwalkanMulaiPada: string | null;
  /** Batas penyelesaian (SLA) atau jadwal selesai. */
  BatasPada: string | null;
  DimulaiPada: string | null;
  DiperbaruiPada: string | null;
  PenugasanId: string | null;
  /** Penugasan belum diterima teknisi ini. */
  PerluRespons: boolean;
  DitugaskanPada: string | null;
  /** Policy `view` masih mengizinkan tiket ini dibuka. */
  DapatDibuka: boolean;
  /** Menunggu Verifikasi tanpa konfirmasi penerima (PRD 8.22). Opsional: paket offline tidak membawanya. */
  MenungguKonfirmasiPenerima?: boolean;
  Aset: AsetRingkasTeknisi | null;
  Lokasi: LokasiRingkasTeknisi | null;
}

/** Tiket dengan transisi status yang diizinkan policy untuk pengguna ini. */
export interface TiketTeknisiLengkap extends TiketTeknisi {
  Deskripsi: string | null;
  StatusTujuan: string[];
  RingkasanPenyelesaian?: string | null;
}

export interface InspeksiMendatangTeknisi {
  Id: string;
  Nomor: string;
  NamaAset: string | null;
  Lokasi: string | null;
  DijadwalkanPada: string | null;
}

/** Props `Lapangan/Teknisi/Beranda`. */
export interface PropsBerandaTeknisi extends PropsLapangan {
  tiket: TiketTeknisi[];
  selesai: TiketTeknisi[];
  inspeksi: InspeksiMendatangTeknisi[];
  lokasiSaya: string | null;
}

/** Props `Lapangan/Teknisi/Tugas`. */
export interface PropsTugasTeknisi extends PropsLapangan {
  tiket: TiketTeknisi[];
  selesai: TiketTeknisi[];
}

/** Props `Lapangan/Teknisi/DetailTiket`. */
export interface PropsDetailTiketTeknisi extends PropsLapangan {
  tiket: TiketTeknisiLengkap;
  keluhan: { Nomor: string; Pelapor: string | null; Lokasi: string | null; Deskripsi: string | null } | null;
  daftarPeriksa: { Id: string; NamaTemplat: string | null; Status: string; JumlahButir: number } | null;
  riwayatAset: { JumlahPekerjaan: number; TerakhirDiservisPada: string | null } | null;
}

export interface ButirChecklistTeknisi {
  Id: string;
  Urutan: number;
  Pertanyaan: string;
  TipeJawaban: string;
  Satuan: string | null;
  Wajib: boolean;
  Pilihan: string[] | null;
  NilaiMinimum: number | null;
  NilaiMaksimum: number | null;
}

export interface JawabanChecklistTeknisi {
  ButirTemplatDaftarPeriksaId: string;
  NilaiTeks?: string | null;
  NilaiAngka?: number | null;
  NilaiBoolean?: boolean | null;
  Catatan?: string | null;
  Sesuai?: boolean | null;
}

export interface ChecklistTeknisi {
  Id: string;
  PerintahKerjaId: string | null;
  AsetId: string | null;
  Status: string;
  Skor: number | null;
  NamaTemplat: string | null;
  VersiTemplat: number | null;
  Catatan: string | null;
  Butir: ButirChecklistTeknisi[];
  Jawaban: JawabanChecklistTeknisi[];
}

export interface PermintaanSukuCadangTeknisi {
  Id: string;
  Jumlah: number;
  Status: string;
  DibuatPada: string;
  NamaSukuCadang: string | null;
  KodeSukuCadang: string | null;
  Satuan: string | null;
  NamaGudang: string | null;
  PerintahKerjaId?: string;
  NomorTiket?: string | null;
  JudulTiket?: string | null;
}

export interface FotoTiketTeknisi {
  Id: string;
  Kategori: 'FotoSebelum' | 'FotoSesudah' | string;
  Keterangan: string | null;
  DibuatPada: string;
  /** Thumbnail untuk grid foto. */
  Url: string | null;
  /** Ukuran penuh, dibuka saat foto diketuk. */
  UrlUnduh: string | null;
}

/** Cara penerima mengonfirmasi pekerjaan (PRD 8.22). */
export type MetodeKonfirmasiPenerima = 'Pelapor' | 'PindaiQr' | 'TandaTanganPerangkat';

/** Satu konfirmasi penerima (`KonfirmasiPenerimaResource::ringkas`). */
export interface KonfirmasiPenerima {
  Id: string;
  Metode: MetodeKonfirmasiPenerima;
  LabelMetode: string;
  Hasil: 'Diterima' | 'MasihBermasalah';
  NamaPenerima: string;
  JabatanPenerima: string | null;
  Alasan: string | null;
  Ulasan: string | null;
  Penilaian: number | null;
  Berlaku: boolean;
  DikonfirmasiPada: string;
  /** Rute terotorisasi dasbor; layar Mode Lapangan tidak memuatnya. */
  UrlTandaTangan: string | null;
}

/** QR konfirmasi penerima dari server (`TautanKonfirmasiPenerima::buat`). */
export interface QrKonfirmasiPenerima {
  Url: string;
  /** SVG QR buatan server (bacon/bacon-qr-code), aman disisipkan. */
  Svg: string;
  BerlakuSampai: string;
}

/** Status konfirmasi yang dibaca berkala layar QR teknisi. */
export interface StatusKonfirmasiPenerima {
  Status: string;
  Konfirmasi: KonfirmasiPenerima | null;
  Terakhir: KonfirmasiPenerima | null;
}

/** Props `Lapangan/Teknisi/Kerjakan` (layar 07–12 dalam satu halaman). */
export interface PropsKerjakanTeknisi extends PropsLapangan {
  tiket: TiketTeknisiLengkap;
  daftarPeriksa: ChecklistTeknisi | null;
  kodeKegagalan: { Id: string; Kode: string; Nama: string }[];
  analisis: { KodeMasalahId: string | null; AkarMasalah: string; TindakanKorektif: string } | null;
  permintaanSukuCadang: PermintaanSukuCadangTeknisi[];
  foto: FotoTiketTeknisi[];
  waktuKerja: { TotalMenit: number; MulaiPertama: string | null };
  berikutnya: TiketTeknisi | null;
  /** Konfirmasi "Diterima" siklus ini; teknisi tidak pernah dikunci olehnya (PRD 8.22). */
  konfirmasiPenerima: KonfirmasiPenerima | null;
}

/** Hasil pencarian suku cadang untuk lembar "Minta suku cadang". */
export interface SukuCadangDicariTeknisi {
  Id: string;
  Kode: string;
  Nama: string;
  NomorBagian: string | null;
  Satuan: string | null;
  Stok: { GudangId: string; NamaGudang: string; TersediaBersih: number }[];
}

/** Aset hasil pindai atau pilihan daftar (`PenyusunLayarTeknisi::detailAset`). */
export interface AsetDitemukanTeknisi extends AsetRingkasTeknisi {
  Status: string | null;
  TingkatKritis: string | null;
  MerekTipe: string | null;
  GaransiBerakhirPada: string | null;
  ServisTerakhirPada: string | null;
  TiketSaya: TiketTeknisi | null;
  Inspeksi: { Id: string; Nomor: string; DijadwalkanPada: string | null } | null;
  BolehLapor: boolean;
  BolehLihatRiwayat: boolean;
  /** Teknisi yang ditugaskan pada tiket aktif aset ini (atau pemegang `Aset.Ubah`) boleh menambah foto. */
  BolehTambahFoto: boolean;
}

/** Props `Lapangan/Teknisi/Pindai`. */
export interface PropsPindaiTeknisi extends PropsLapangan {
  asetDitemukan: AsetDitemukanTeknisi | null;
  galatPindai: string | null;
  tanpaIzin: boolean;
}

/** Props `Lapangan/Teknisi/Aset`. */
export interface PropsAsetTeknisi extends PropsLapangan {
  aset: AsetRingkasTeknisi[];
  cari: string;
  asetTerpilih: AsetDitemukanTeknisi | null;
  bolehLihat: boolean;
}

export interface KejadianRiwayatAset {
  Id: string;
  Jenis: 'PerintahKerja' | 'Inspeksi';
  Kategori: string | null;
  Judul: string;
  Status: string | null;
  Prioritas: string | null;
  Pada: string | null;
  DurasiMenit: number | null;
  Keterangan: string | null;
  Teknisi: string[];
}

/** Props `Lapangan/Teknisi/RiwayatAset`. */
export interface PropsRiwayatAsetTeknisi extends PropsLapangan {
  aset: AsetRingkasTeknisi;
  ringkasan: { PekerjaanTahunIni: number; PersenBeroperasi: number; HariAntarKerusakan: number | null };
  linimasa: KejadianRiwayatAset[];
}

/** Props `Lapangan/Teknisi/SukuCadang`. */
export interface PropsSukuCadangTeknisi extends PropsLapangan {
  permintaan: PermintaanSukuCadangTeknisi[];
}

/** Props `Lapangan/Teknisi/Siapkan`. */
export interface PropsSiapkanTeknisi extends PropsLapangan {
  tiketId: string[];
  asetId: string[];
  jumlah: { Tiket: number; Aset: number; Lokasi: number; Templat: number; SukuCadang: number };
}

/** Props `Lapangan/Teknisi/Konflik`. */
export interface PropsKonflikTeknisi extends PropsLapangan {
  antrian: AntrianServer;
  tiket: TiketTeknisi | null;
  perubahanServer: {
    Status: string | null;
    Catatan: string | null;
    Oleh: string | null;
    Jabatan: string | null;
    Pada: string;
  } | null;
}

// Pelapor (D)

/** Status keluhan (Pemeliharaan `StatusKeluhan`). */
export type StatusKeluhanPelapor =
  'Baru' | 'Ditinjau' | 'Diterima' | 'Diproses' | 'Selesai' | 'Ditutup' | 'Ditolak' | 'Dibatalkan';

/** Urgensi berbahasa awam (server `UrgensiPelapor`), disimpan sebagai usulan pada keluhan. */
export type UrgensiLaporan = 'TidakBuruBuru' | 'MenggangguKerja' | 'KerjaTerhenti' | 'Berbahaya';

/** Lokasi dengan label induk, mis. `{ Nama: 'Lt. 12', Label: 'Menara A · Lt. 12' }`. */
export interface LokasiPelapor {
  Id: string;
  Nama: string;
  Label: string;
}

/** Keluhan terbuka pada satu aset (pencegah laporan ganda). Keluhan orang lain tanpa nama teknisi. */
export interface LaporanTerbukaAset {
  Id: string;
  Nomor: string;
  Judul: string;
  Status: StatusKeluhanPelapor;
  MilikSaya: boolean;
  NamaTeknisi: string | null;
}

export interface AsetPelapor {
  Id: string;
  KodeAset: string;
  Nama: string;
  Kategori: string | null;
  /** `KondisiAset`: Baik · PerluPerhatian · Rusak. */
  Kondisi: string | null;
  Status: string;
  LokasiId: string | null;
  LokasiNama: string | null;
  LaporanTerbuka: LaporanTerbukaAset[];
  /** "Menara A · Lt. 12". */
  LokasiLabel: string | null;
  /** Thumbnail foto utama (PRD 8.4 "Foto Aset"); `null` = ikon 3D kategori. */
  FotoUtamaThumbnailUrl: string | null;
}

export interface KategoriLaporan {
  Id: string;
  Nama: string;
  AsetWajib?: boolean;
}

/** Teknisi pada perintah kerja dari keluhan milik pelapor. */
export interface TeknisiLaporan {
  Nama: string;
  Telepon: string | null;
  Jabatan: string | null;
  StatusPekerjaan: string;
  DitugaskanPada: string | null;
  DimulaiPada: string | null;
  /** Ringkasan penyelesaian dari teknisi. */
  Ringkasan: string | null;
}

/** Satu keluhan milik pelapor. Waktu dalam ISO 8601. */
export interface LaporanPelapor {
  Id: string;
  Nomor: string;
  Judul: string;
  Deskripsi: string;
  Status: StatusKeluhanPelapor;
  Versi: number;
  KategoriNama: string | null;
  Aset: { Id: string; KodeAset: string; Nama: string; Kategori: string | null } | null;
  LokasiNama: string | null;
  DilaporkanPada: string | null;
  BatasResponsPada: string | null;
  BatasPenyelesaianPada: string | null;
  DiresolusikanPada: string | null;
  DitutupPada: string | null;
  Rating: number | null;
  Ulasan: string | null;
  /** Waktu perubahan status terakhir (daftar). */
  StatusSejak?: string | null;
  Teknisi?: TeknisiLaporan | null;
  LokasiLabel?: string | null;
  /**
   * Konfirmasi pelapor di tahap perintah kerja (PRD 8.22): `Diminta` = pekerjaan diserahkan
   * teknisi dan menunggu jawabanmu; `Dikonfirmasi` = kamu sudah menjawab "Sudah beres".
   */
  KonfirmasiPekerjaan?: 'Diminta' | 'Dikonfirmasi' | null;
}

export interface RiwayatLaporan {
  StatusSebelum: StatusKeluhanPelapor | null;
  StatusSesudah: StatusKeluhanPelapor;
  Catatan: string | null;
  DiubahPada: string | null;
  NamaPengubah: string | null;
  OlehSaya: boolean;
}

export interface PropsBerandaPelapor extends PropsLapangan {
  lokasi: LokasiPelapor | null;
  kategori: KategoriLaporan[];
  laporanAktif: LaporanPelapor[];
  jumlahAktif: number;
}

export interface PropsLaporPelapor extends PropsLapangan {
  bolehLihatAset: boolean;
  lokasi: LokasiPelapor | null;
  pilihanLokasi: LokasiPelapor[];
  aset: AsetPelapor[];
  asetTerpilih: AsetPelapor | null;
  /** `?aset=`/`?kode=` diberikan tetapi asetnya tidak ada di lingkup pelapor. */
  asetTidakDitemukan: boolean;
  kategori: KategoriLaporan[];
  kategoriAwal: string | null;
  kontak: { Nama: string; Telepon: string | null };
}

export interface PropsLaporanPelapor extends PropsLapangan {
  lokasi: LokasiPelapor | null;
  laporan: LaporanPelapor[];
  jumlah: { Aktif: number; PerluKonfirmasi: number; Selesai: number };
}

export interface PropsLacakPelapor extends PropsLapangan {
  laporan: LaporanPelapor;
  riwayat: RiwayatLaporan[];
  jumlahFoto: number;
}

export interface FotoLaporan {
  BerkasId: string;
  Kategori: string | null;
  Nama: string | null;
}

/** Foto Sesudah dari teknisi pada perintah kerja keluhan milik pelapor. */
export interface FotoSesudahLaporan {
  BerkasId: string;
  Nama: string | null;
}

export interface PropsKonfirmasiPelapor extends PropsLapangan {
  laporan: LaporanPelapor;
  /** `Pekerjaan`: konfirmasi penerima saat teknisi menyerahkan pekerjaan; `Keluhan`: keluhan Selesai. */
  tahap: 'Pekerjaan' | 'Keluhan';
  pekerjaan: {
    Id: string;
    Nomor: string;
    RingkasanPenyelesaian: string | null;
    DiserahkanPada: string | null;
  } | null;
  foto: FotoLaporan[];
  fotoSesudah: FotoSesudahLaporan[];
}

/** Ringkasan pekerjaan di halaman konfirmasi hasil pindai QR. */
export interface PekerjaanKonfirmasiPenerima {
  Id: string;
  Nomor: string;
  Judul: string;
  Status: string;
  RingkasanPenyelesaian: string | null;
  Aset: { Nama: string; KodeAset: string; Kategori: string | null } | null;
  Lokasi: string | null;
  Teknisi: string[];
  DimulaiPada: string | null;
  DiserahkanPada: string | null;
}

/** Props `Lapangan/KonfirmasiPenerima` (cara 2, PRD 8.22). */
export interface PropsKonfirmasiPenerima extends PropsLapangan {
  keadaan: 'Siap' | 'SudahDikonfirmasi' | 'TidakMenunggu' | 'TanpaAkses' | 'Kedaluwarsa' | 'TautanTidakSah';
  pesan: string | null;
  pekerjaan: PekerjaanKonfirmasiPenerima | null;
  konfirmasi: { NamaPenerima: string; DikonfirmasiPada: string; OlehSaya: boolean } | null;
  /** Jalur halaman ini beserta tanda tangan tautannya, tujuan formulir. */
  urlKirim: string;
}

/** Props `Lapangan/KonfirmasiPenerimaHasil`. */
export interface PropsKonfirmasiPenerimaHasil extends PropsLapangan {
  pekerjaan: PekerjaanKonfirmasiPenerima;
  hasil: 'Diterima' | 'MasihBermasalah';
  dikonfirmasiPada: string;
}

/**
 * Props `Lapangan/Pelapor/Pantau`: laporan rekan, hanya garis waktu status.
 * Sengaja tanpa nama pelapor/teknisi, keterangan, dan foto (PRD 8.20).
 */
export interface PropsPantauPelapor extends PropsLapangan {
  laporan: {
    Nomor: string;
    Judul: string;
    Status: StatusKeluhanPelapor;
    Aset: { KodeAset: string; Nama: string; Kategori: string | null } | null;
    LokasiLabel: string | null;
  };
  riwayat: { Status: StatusKeluhanPelapor; Pada: string | null }[];
}

export interface PropsLaporanTunggal extends PropsLapangan {
  laporan: LaporanPelapor;
}

export interface PropsAsetPelapor extends PropsLapangan {
  bolehLihat: boolean;
  lokasi: LokasiPelapor | null;
  pilihanLokasi: LokasiPelapor[];
  aset: AsetPelapor[];
}
