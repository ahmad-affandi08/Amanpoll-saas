export const ruteAuth = {
  login: '/login',
  daftar: '/daftar',
  logout: '/logout',
  lupaKataSandi: '/lupa-kata-sandi',
  resetKataSandi: (penggunaId: string, token: string) => `/reset-kata-sandi/${penggunaId}/${token}`,
};
