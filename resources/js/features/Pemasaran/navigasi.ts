/**
 * Halaman konsol Growth & Marketing (MARKETING.md 4). Satu daftar untuk kartu pintasan
 * di Ringkasan dan submenu sidebar konsol platform, supaya keduanya tidak pernah berbeda.
 * `modul` adalah fitur platform (`KatalogFiturPlatform`) yang harus aktif; rutenya
 * mengembalikan 404 bila modulnya mati, jadi tautannya ikut disembunyikan.
 */
export const HALAMAN_PEMASARAN = [
  {
    label: 'Dashboard Growth',
    href: '/admin-platform/pemasaran/growth',
    keterangan: 'Funnel, KPI, revenue per channel, dan alert growth.',
    modul: null,
    izin: 'platform.analytics.lihat',
  },
  {
    label: 'Prospek',
    href: '/admin-platform/pemasaran/prospek',
    keterangan: 'Pipeline, skor, dan aktivitas prospek.',
    modul: 'marketing.crm',
    izin: 'platform.prospek.lihat',
  },
  {
    label: 'Aturan Skor',
    href: '/admin-platform/pemasaran/prospek/aturan-skor',
    keterangan: 'Bobot tiap sinyal terhadap skor prospek.',
    modul: 'marketing.crm',
    izin: 'platform.prospek.lihat',
  },
  {
    label: 'Trial',
    href: '/admin-platform/pemasaran/trial',
    keterangan: 'Trial berjalan beserta checklist aktivasinya.',
    modul: 'marketing.crm',
    izin: 'platform.prospek.lihat',
  },
  {
    label: 'Kampanye',
    href: '/admin-platform/pemasaran/kampanye',
    keterangan: 'Kampanye dan biayanya.',
    modul: 'marketing.analytics',
    izin: 'platform.kampanye.lihat',
  },
  {
    label: 'Demo Produk',
    href: '/admin-platform/pemasaran/demo',
    keterangan: 'Sandbox demo, sesinya, dan reset datasetnya.',
    modul: null,
    izin: 'platform.pemasaran.lihat',
  },
  {
    label: 'Halaman Publik',
    href: '/admin-platform/pemasaran/halaman',
    keterangan: 'Landing page beserta versinya.',
    modul: 'marketing.cms',
    izin: 'platform.halaman.lihat',
  },
  {
    label: 'Formulir',
    href: '/admin-platform/pemasaran/formulir',
    keterangan: 'Formulir publik dan kirimannya.',
    modul: 'marketing.cms',
    izin: 'platform.halaman.lihat',
  },
  {
    label: 'Konten & SEO',
    href: '/admin-platform/pemasaran/konten',
    keterangan: 'Artikel berversi, keyword manager, dan metadata situs publik.',
    modul: 'marketing.cms',
    izin: 'platform.konten.lihat',
  },
  {
    label: 'Redirect',
    href: '/admin-platform/pemasaran/redirect',
    keterangan: 'Peta alih alamat situs publik.',
    modul: 'marketing.cms',
    izin: 'platform.halaman.lihat',
  },
  {
    label: 'Otomasi',
    href: '/admin-platform/pemasaran/otomasi',
    keterangan: 'Pemicu, kondisi, jeda, dan aksi beserta eksekusinya.',
    modul: null,
    izin: 'platform.otomasi.lihat',
  },
  {
    label: 'Template Email',
    href: '/admin-platform/pemasaran/email/template',
    keterangan: 'Naskah email pemasaran dan variabelnya.',
    modul: null,
    izin: 'platform.email.lihat',
  },
  {
    label: 'Sequence Email',
    href: '/admin-platform/pemasaran/email/sequence',
    keterangan: 'Rangkaian email onboarding dan jadwalnya.',
    modul: null,
    izin: 'platform.email.lihat',
  },
  {
    label: 'WhatsApp',
    href: '/admin-platform/pemasaran/whatsapp',
    keterangan: 'Template, persetujuan penyedia, dan menu percakapan.',
    modul: 'marketing.whatsapp',
    izin: 'platform.whatsapp.lihat',
  },
  {
    label: 'Konten Sosial',
    href: '/admin-platform/pemasaran/sosial',
    keterangan: 'Satu konten, banyak distribusi, beserta jadwal terbitnya.',
    modul: 'marketing.social',
    izin: 'platform.konten.lihat',
  },
  {
    label: 'Eksperimen A/B',
    href: '/admin-platform/pemasaran/eksperimen',
    keterangan: 'Varian, peserta, dan ambang sampel sebelum pemenang boleh dinyatakan.',
    modul: 'marketing.experiment',
    izin: 'platform.eksperimen.kelola',
  },
  {
    label: 'Referral',
    href: '/admin-platform/pemasaran/referral',
    keterangan: 'Program referral, kode pelanggan, dan imbalannya.',
    modul: null,
    izin: 'platform.referral.lihat',
  },
  {
    label: 'Consent dan Supresi',
    href: '/admin-platform/pemasaran/email/konsen',
    keterangan: 'Siapa boleh dikirimi pesan, dan permintaan penghapusan data.',
    modul: null,
    izin: 'platform.email.lihat',
  },
] as const;
