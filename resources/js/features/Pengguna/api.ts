export const rutePengguna = {
  index: '/platform/pengguna',
  detail: (id: string) => `/platform/pengguna/${id}`,
  peran: (id: string) => `/platform/pengguna/${id}/peran`,
  peranDetail: (penggunaPeranId: string) => `/platform/pengguna-peran/${penggunaPeranId}`,
  status: (id: string) => `/platform/pengguna/${id}/status`,
  bebanKerja: (id: string) => `/platform/pengguna/${id}/beban-kerja`,
  aktivitas: (id: string) => `/platform/pengguna/${id}/aktivitas`,
};
