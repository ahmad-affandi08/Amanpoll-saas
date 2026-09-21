export const ruteMutasiAset = {
  index: '/mutasi-aset',
  detailDetail: (id: string) => `/mutasi-aset/detail/${id}`,
  detail: (id: string) => `/mutasi-aset/${id}`,
  batalkan: (id: string) => `/mutasi-aset/${id}/batalkan`,
  detail2: (id: string) => `/mutasi-aset/${id}/detail`,
  eksekusi: (id: string) => `/mutasi-aset/${id}/eksekusi`,
  submit: (id: string) => `/mutasi-aset/${id}/submit`,
};
