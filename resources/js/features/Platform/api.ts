/** Rute konsol admin platform, terpisah dari konsol pemasaran yang punya akarnya sendiri. */
const AKAR = '/admin-platform';

export const rutePlatform = {
  login: `${AKAR}/login`,
  logout: `${AKAR}/logout`,

  paket: `${AKAR}/paket`,
  paketDetail: (id: string) => `${AKAR}/paket/${id}`,

  langganan: `${AKAR}/langganan`,
  langgananTagihan: (id: string) => `${AKAR}/langganan/${id}/tagihan`,
  langgananPerpanjang: (id: string) => `${AKAR}/langganan/${id}/perpanjang`,
  langgananBatalkan: (id: string) => `${AKAR}/langganan/${id}/batalkan`,
};
