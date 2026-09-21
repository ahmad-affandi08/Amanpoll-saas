export const rutePenghapusanAset = {
  index: '/penghapusan-aset',
  detailDetail: (id: string) => `/penghapusan-aset/detail/${id}`,
  detail: (id: string) => `/penghapusan-aset/${id}`,
  batalkan: (id: string) => `/penghapusan-aset/${id}/batalkan`,
  detail2: (id: string) => `/penghapusan-aset/${id}/detail`,
  eksekusi: (id: string) => `/penghapusan-aset/${id}/eksekusi`,
  submit: (id: string) => `/penghapusan-aset/${id}/submit`,
};
