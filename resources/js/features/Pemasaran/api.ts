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

  konten: `${AKAR}/konten`,
  kontenDetail: (id: string) => `${AKAR}/konten/${id}`,

  formulir: `${AKAR}/formulir`,
  formulirDetail: (kode: string) => `${AKAR}/formulir/${kode}`,

  otomasi: `${AKAR}/otomasi`,
  demo: `${AKAR}/demo`,
  trial: `${AKAR}/trial`,
  referral: `${AKAR}/referral`,
  partner: `${AKAR}/partner`,
  eksperimen: `${AKAR}/eksperimen`,
  redirect: `${AKAR}/redirect`,
  sosial: `${AKAR}/sosial`,
  whatsapp: `${AKAR}/whatsapp`,
  growth: `${AKAR}/growth`,

  emailKonsen: `${AKAR}/email/konsen`,
  emailSequence: `${AKAR}/email/sequence`,
  emailTemplate: `${AKAR}/email/template`,

  pengaturan: `${AKAR}/pengaturan`,
  pengaturanFitur: `${AKAR}/pengaturan/fitur`,
  pengaturanKonfigurasi: `${AKAR}/pengaturan/konfigurasi`,
};
