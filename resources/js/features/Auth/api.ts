export const ruteAuth = {
  login: '/login',
  logout: '/logout',
  lupaKataSandi: '/lupa-kata-sandi',
  resetKataSandi: (penggunaId: string, token: string) => `/reset-kata-sandi/${penggunaId}/${token}`,
};
