/**
 * Rute konsol Growth & Marketing, seluruhnya di host dashboard.
 *
 * Akar tiap bagian dikumpulkan di sini supaya tidak ditulis ulang di tiap
 * halaman; jalur turunan yang hanya dipakai satu halaman tetap disusun di
 * halamannya dari akar ini.
 */
const AKAR = '/admin-platform/pemasaran';

export const rutePemasaran = {
  ringkasan: AKAR,

  prospek: `${AKAR}/prospek`,
  prospekDetail: (id: string) => `${AKAR}/prospek/${id}`,
  prospekAktivitas: (id: string) => `${AKAR}/prospek/${id}/aktivitas`,
  prospekTahap: (id: string) => `${AKAR}/prospek/${id}/tahap`,
  prospekImpor: `${AKAR}/prospek/impor`,
  prospekEksporCsv: `${AKAR}/prospek/ekspor/csv`,
  aturanSkor: `${AKAR}/prospek/aturan-skor`,

  kampanye: `${AKAR}/kampanye`,
  kampanyeDetail: (id: string) => `${AKAR}/kampanye/${id}`,

  halaman: `${AKAR}/halaman`,
  halamanBaru: `${AKAR}/halaman/baru`,
  halamanDetail: (id: string) => `${AKAR}/halaman/${id}`,
  halamanPratinjau: (id: string, versiId: string) => `${AKAR}/halaman/${id}/pratinjau/${versiId}`,
  halamanKembalikan: (id: string, versiId: string) => `${AKAR}/halaman/${id}/kembalikan/${versiId}`,

  konten: `${AKAR}/konten`,
  kontenDetail: (id: string) => `${AKAR}/konten/${id}`,

  formulir: `${AKAR}/formulir`,
  formulirDetail: (kode: string) => `${AKAR}/formulir/${kode}`,

  otomasi: `${AKAR}/otomasi`,
  demo: `${AKAR}/demo`,
  trial: `${AKAR}/trial`,
  referral: `${AKAR}/referral`,
  partner: `${AKAR}/partner`,
  partnerDetail: (id: string) => `${AKAR}/partner/${id}`,
  partnerProgram: `${AKAR}/partner/program`,
  partnerProgramDetail: (kode: string) => `${AKAR}/partner/program/${kode}`,
  partnerAturan: `${AKAR}/partner/aturan`,
  partnerAturanDetail: (id: string) => `${AKAR}/partner/aturan/${id}`,
  partnerLeadTerima: (id: string) => `${AKAR}/partner/lead/${id}/terima`,
  partnerLeadTolak: (id: string) => `${AKAR}/partner/lead/${id}/tolak`,
  partnerKomisiSetujui: (id: string) => `${AKAR}/partner/komisi/${id}/setujui`,
  partnerKomisiBatalkan: (id: string) => `${AKAR}/partner/komisi/${id}/batalkan`,
  partnerPayoutBaru: (partnerId: string) => `${AKAR}/partner/${partnerId}/payout`,
  partnerPayoutBayar: (id: string) => `${AKAR}/partner/payout/${id}/bayar`,
  partnerPayoutBatalkan: (id: string) => `${AKAR}/partner/payout/${id}/batalkan`,

  eksperimen: `${AKAR}/eksperimen`,
  redirect: `${AKAR}/redirect`,
  sosial: `${AKAR}/sosial`,
  sosialKonten: (kontenId: string) => `${AKAR}/sosial/${kontenId}`,
  sosialDistribusi: (kontenId: string) => `${AKAR}/sosial/${kontenId}/distribusi`,
  sosialDistribusiDetail: (kontenId: string, id: string) => `${AKAR}/sosial/${kontenId}/distribusi/${id}`,

  whatsapp: `${AKAR}/whatsapp`,
  whatsappMenu: `${AKAR}/whatsapp/menu`,
  whatsappTemplate: `${AKAR}/whatsapp/template`,
  whatsappTemplateDetail: (id: string) => `${AKAR}/whatsapp/template/${id}`,
  whatsappTemplateAjukan: (id: string) => `${AKAR}/whatsapp/template/${id}/ajukan`,
  whatsappTemplatePeriksa: (id: string) => `${AKAR}/whatsapp/template/${id}/periksa`,
  whatsappTemplateKeputusan: (id: string) => `${AKAR}/whatsapp/template/${id}/keputusan`,
  growth: `${AKAR}/growth`,
  growthAlertSelesai: (id: string) => `${AKAR}/growth/alert/${id}/selesai`,

  emailKonsen: `${AKAR}/email/konsen`,
  emailSequence: `${AKAR}/email/sequence`,
  emailTemplate: `${AKAR}/email/template`,

  pengaturan: `${AKAR}/pengaturan`,
  pengaturanFitur: `${AKAR}/pengaturan/fitur`,
  pengaturanKonfigurasi: `${AKAR}/pengaturan/konfigurasi`,
};
