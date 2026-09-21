export const ruteKepatuhan = {
  index: '/kepatuhan',
  standar: '/kepatuhan/standar',
  standarDetail: (id: string) => `/kepatuhan/standar/${id}`,
  persyaratan: (standarId: string) => `/kepatuhan/standar/${standarId}/persyaratan`,
  persyaratanDetail: (standarId: string, persyaratanId: string) =>
    `/kepatuhan/standar/${standarId}/persyaratan/${persyaratanId}`,
  tugaskan: '/kepatuhan/tugaskan',
  pemeriksaan: (kewajibanId: string) => `/kepatuhan/kewajiban/${kewajibanId}/pemeriksaan`,
  kewajibanDetail: (kewajibanId: string) => `/kepatuhan/kewajiban/${kewajibanId}`,
};
