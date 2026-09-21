export const ruteSukuCadang = {
  index: '/suku-cadang',
  kompatibilitas: '/kompatibilitas-suku-cadang',
  kompatibilitasDetail: (id: string) => `/kompatibilitas-suku-cadang/${id}`,
  detail: (id: string) => `/suku-cadang/${id}`,
  kelompok: (id: string) => `/suku-cadang/${id}/kelompok`,
};
