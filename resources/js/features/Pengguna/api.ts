export const rutePengguna = {
  index: '/platform/pengguna',
  detail: (id: string) => `/platform/pengguna/${id}`,
  peran: (id: string) => `/platform/pengguna/${id}/peran`,
  status: (id: string) => `/platform/pengguna/${id}/status`,
};
