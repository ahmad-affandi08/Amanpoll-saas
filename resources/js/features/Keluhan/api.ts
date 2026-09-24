export const ruteKeluhan = {
  index: '/pemeliharaan/keluhan',
  detail: (id: string) => `/pemeliharaan/keluhan/${id}`,
  prioritas: (id: string) => `/pemeliharaan/keluhan/${id}/prioritas`,
  status: (id: string) => `/pemeliharaan/keluhan/${id}/status`,
  unitPengelola: (id: string) => `/pemeliharaan/keluhan/${id}/unit-pengelola`,
  ekspor: '/pemeliharaan/keluhan/ekspor',
};
