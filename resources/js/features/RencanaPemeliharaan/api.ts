export const ruteRencanaPemeliharaan = {
  index: '/preventif-inspeksi/rencana-pemeliharaan',
  jalankanScheduler: '/preventif-inspeksi/rencana-pemeliharaan/jalankan-scheduler',
  detail: (id: string) => `/preventif-inspeksi/rencana-pemeliharaan/${id}`,
  aset: (id: string) => `/preventif-inspeksi/rencana-pemeliharaan/${id}/aset`,
  asetDetail: (id1: string, id2: string) => `/preventif-inspeksi/rencana-pemeliharaan/${id1}/aset/${id2}`,
  ekspor: '/preventif-inspeksi/rencana-pemeliharaan/ekspor',
};
