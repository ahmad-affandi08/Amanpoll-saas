export const ruteKalibrasi = {
  jenis: '/kalibrasi/jenis',
  jenisDetail: (id: string) => `/kalibrasi/jenis/${id}`,
  jenisTitikUkur: (id: string) => `/kalibrasi/jenis/${id}/titik-ukur`,
  pelaksanaan: '/kalibrasi/pelaksanaan',
  pelaksanaanDetail: (id: string) => `/kalibrasi/pelaksanaan/${id}`,
  pelaksanaanFinalisasi: (id: string) => `/kalibrasi/pelaksanaan/${id}/finalisasi`,
  pelaksanaanHasilTitikUkur: (id: string) => `/kalibrasi/pelaksanaan/${id}/hasil-titik-ukur`,
  rencana: '/kalibrasi/rencana',
  rencanaJalankanPengingat: '/kalibrasi/rencana/jalankan-pengingat',
  rencanaDetail: (id: string) => `/kalibrasi/rencana/${id}`,
  titikUkurDetail: (id: string) => `/kalibrasi/titik-ukur/${id}`,
};
