export const ruteMutasiStok = {
  index: '/mutasi-stok',
  detail: (id: string) => `/mutasi-stok/${id}`,
  batalkan: (id: string) => `/mutasi-stok/${id}/batalkan`,
  detail2: (id: string) => `/mutasi-stok/${id}/detail`,
  posting: (id: string) => `/mutasi-stok/${id}/posting`,
  barisDetail: (detailId: string) => `/detail-mutasi-stok/${detailId}`,
  ekspor: '/mutasi-stok/ekspor',
};
