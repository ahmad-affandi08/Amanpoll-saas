export const ruteReservasiSukuCadang = {
  index: '/reservasi-suku-cadang',
  konsumsi: (id: string) => `/reservasi-suku-cadang/${id}/konsumsi`,
  lepaskan: (id: string) => `/reservasi-suku-cadang/${id}/lepaskan`,
  ekspor: '/reservasi-suku-cadang/ekspor',
};
