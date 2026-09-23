/** Rute langganan sisi tenant; katalog paket dan langganan lintas tenant ada di rutePlatform. */
export const ruteLangganan = {
  index: '/langganan',
  tagihanBayar: (id: string) => `/langganan/tagihan/${id}/bayar`,
  ekspor: '/langganan/ekspor',
};
