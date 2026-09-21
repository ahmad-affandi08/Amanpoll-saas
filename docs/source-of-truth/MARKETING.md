# MARKETING.md — Amanpoll Growth & Marketing Platform

> **Status:** Source of Truth Tambahan  
> **Produk:** Amanpoll  
> **Tujuan:** Menjadikan pemasaran, lead generation, trial, nurturing, conversion, referral, partner, dan analytics sebagai bagian dari Amanpoll yang dapat dikendalikan melalui Dashboard Platform/Developer tanpa membutuhkan karyawan marketing untuk pekerjaan rutin.

## 0. Instruksi untuk Claude Code

Dokumen ini bukan aplikasi marketing terpisah. Implementasikan sebagai bagian dari Amanpoll dan tetap tunduk pada `PRD.md`, `TASK.md`, `DESIGN.md`, `Amanpoll_Database_MySQL.sql`, serta arsitektur Modular Monolith + DDD pragmatis yang sudah digunakan.

Aturan implementasi:

- Jangan melanggar urutan dependency pada `TASK.md`.
- Jangan menduplikasi domain Langganan, Billing, Notifikasi, Integrasi, Audit, Persetujuan, Organisasi, IAM, dan Feature Flag.
- Integrasikan domain Pemasaran melalui application service, domain event, outbox, idempotency, dan adapter.
- Business naming memakai Bahasa Indonesia.
- Method framework seperti `handle()`, `rules()`, `authorize()`, `boot()`, `up()`, `down()` tetap mengikuti framework.
- Controller tipis; business logic berada di Application/Domain.
- Semua perubahan penting memiliki audit trail.
- Semua job asynchronous harus kompatibel dengan Niagahoster shared hosting: database queue + cron.
- Jangan mewajibkan Redis, Horizon, Supervisor, systemd, Docker, Octane, FrankenPHP, atau daemon permanen.
- Jangan hard-code landing page, CTA, trial duration, campaign, email sequence, WhatsApp flow, pricing presentation, referral reward, dan SEO metadata bila dapat dibuat configurable.
- Secret provider tetap di environment/secret storage.
- Semua UI mengikuti `DESIGN.md`: light only, IBM Plex Sans, steel-blue/navy + safety amber, responsive, radius moderat, tanpa AI-slop visual.
- Catat progress di `PROGRESS.md`.

---

# 1. Sasaran Sistem

Amanpoll harus mempunyai **Growth Operating System** internal:

```text
Traffic
→ Landing Page
→ Form / Demo
→ Lead
→ Nurturing
→ Trial
→ Activation
→ Subscription
→ Retention
→ Referral
→ Partner
→ Revenue
```

Targetnya:

- marketing full-online;
- pekerjaan rutin otomatis;
- founder hanya menangani lead strategis/enterprise bila diperlukan;
- semua channel mempunyai attribution;
- campaign dan content dapat dikelola tanpa deploy;
- public website dan aplikasi menggunakan konfigurasi platform yang sama;
- semua conversion dapat ditelusuri sampai revenue.

---

# 2. Posisi Domain

Tambahkan bounded context:

```text
app/Domain/Pemasaran/
├── Application/
│   ├── Actions/
│   ├── Commands/
│   ├── DTO/
│   ├── Queries/
│   └── Services/
├── Domain/
│   ├── Events/
│   ├── Exceptions/
│   ├── Policies/
│   ├── Repositories/
│   ├── Rules/
│   └── ValueObjects/
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Models/
│   │   ├── QueryBuilders/
│   │   └── Repositories/
│   └── Services/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Notifications/
└── Support/
```

Frontend:

```text
resources/js/features/pemasaran/
├── dashboard/
├── prospek/
├── crm/
├── kampanye/
├── halaman/
├── formulir/
├── konten/
├── seo/
├── otomasi/
├── email/
├── whatsapp/
├── sosial/
├── trial/
├── referral/
├── partner/
├── eksperimen/
├── attribution/
└── analytics/
```

---

# 3. Navigasi Dashboard Platform

```text
Platform
├── Dashboard
├── Tenant
├── Langganan
├── Billing
├── Feature Flag
├── Audit
├── Integrasi
├── DLQ
└── Growth & Marketing
    ├── Ringkasan
    ├── Prospek
    ├── CRM
    ├── Kampanye
    ├── Landing Page
    ├── Formulir
    ├── Konten & SEO
    ├── Email
    ├── WhatsApp
    ├── Social Media
    ├── Otomasi
    ├── Demo & Trial
    ├── Harga & Penawaran
    ├── Referral
    ├── Partner
    ├── Eksperimen
    ├── Attribution
    ├── Analytics
    └── Pengaturan
```

Semua menu ini hanya untuk role platform yang memiliki permission sesuai.

---

# 4. Dashboard Growth

KPI utama:

- visitor;
- unique visitor;
- landing page view;
- form submit;
- demo started;
- demo completed;
- trial registered;
- trial activated;
- qualified lead;
- paid customer;
- MRR baru;
- visitor → lead;
- visitor → trial;
- trial → activated;
- activated → paid;
- CAC per channel;
- revenue per channel;
- referral conversion;
- partner-sourced revenue.

Funnel utama:

```text
Visitor
↓
Lead
↓
Demo
↓
Trial
↓
Activated
↓
Qualified
↓
Paid
```

Filter:

- tanggal;
- channel;
- campaign;
- industri;
- landing page;
- device;
- paket;
- referral;
- partner.

Alert platform:

- trial conversion turun;
- banyak lead tanpa aktivitas;
- automation gagal;
- email bounce meningkat;
- WhatsApp delivery failure meningkat;
- campaign tidak menghasilkan trial;
- referral reward gagal;
- komisi partner pending;
- landing page conversion anomali.

---

# 5. Prospek & CRM

## 5.1 Sumber lead

- website;
- demo;
- trial;
- email;
- WhatsApp;
- referral;
- partner;
- social;
- organic search;
- campaign;
- import CSV;
- API;
- webhook;
- lead magnet;
- manual.

## 5.2 Data lead

Minimal:

- nama;
- email;
- telepon;
- WhatsApp;
- perusahaan;
- jabatan;
- industri;
- jumlah lokasi;
- estimasi aset;
- estimasi teknisi;
- kota;
- negara;
- source;
- campaign;
- UTM;
- first touch;
- last touch;
- landing page;
- lead score;
- status;
- tags;
- catatan;
- dibuat pada;
- aktivitas terakhir.

## 5.3 State

```text
BARU
→ DIHUBUNGI
→ TERLIBAT
→ DEMO
→ TRIAL
→ AKTIF
→ QUALIFIED
→ MENANG
```

Alternative:

```text
TIDAK_COCOK
HILANG
UNSUBSCRIBE
```

Perubahan stage wajib tercatat di timeline.

## 5.4 Lead score

Rule configurable. Contoh default:

```text
+5  melihat halaman harga
+8  membuka demo
+10 submit form
+15 memulai trial
+20 membuat aset pertama
+15 mengundang user
+15 membuat work order
+10 aktif ≥ 3 hari
-10 email bounce
-20 tidak aktif 14 hari
```

Jangan hard-code score sebagai angka permanen di source.

---

# 6. Timeline Prospek

Satu timeline gabungan:

```text
09:12 Landing page viewed
09:15 Form submitted
09:16 Welcome email sent
10:03 Demo started
10:17 Demo completed
13:20 Pricing viewed
D+1 Trial registered
D+1 First asset created
D+2 Work order created
D+7 Subscription purchased
```

Event berasal dari website, aplikasi, email, WhatsApp, billing, subscription, referral, partner, dan automation.

---

# 7. Landing Page Builder

Landing page dapat dibuat/diubah dari dashboard tanpa deploy.

Tipe:

- general;
- industri;
- fitur;
- use case;
- campaign;
- comparison;
- lead magnet;
- pricing;
- partner;
- referral.

Seed segment awal:

- manufaktur;
- hotel;
- property/facility;
- healthcare;
- workshop;
- pendidikan;
- retail multi-cabang;
- logistik;
- general business.

Block:

- hero;
- trust/logo;
- problem;
- benefit;
- feature;
- screenshot;
- video;
- metric;
- testimonial;
- case study;
- comparison;
- FAQ;
- pricing;
- CTA;
- form;
- tool embed;
- article list;
- footer.

Status:

```text
DRAF
→ REVIEW
→ TERJADWAL
→ TERBIT
→ DIARSIPKAN
```

Wajib:

- revision history;
- versioning;
- scheduled publish;
- scheduled unpublish;
- rollback;
- audit.

---

# 8. CMS Konten & SEO

Jenis konten:

- artikel;
- panduan;
- glossary;
- case study;
- template;
- checklist;
- ebook;
- free tool;
- comparison page;
- integration page.

SEO metadata:

- slug;
- title;
- meta description;
- canonical;
- Open Graph;
- schema type;
- noindex;
- sitemap;
- redirect.

Keyword manager:

```text
Keyword
Cluster
SearchIntent
TargetUrl
Prioritas
Status
Catatan
```

Intent:

```text
INFORMATIONAL
COMMERCIAL
TRANSACTIONAL
NAVIGATIONAL
```

Support redirect:

```text
301
302
410
```

---

# 9. Lead Magnet & Form Builder

Lead magnet:

- template preventive maintenance;
- template work order;
- checklist inspeksi;
- template stock opname;
- asset register;
- kalkulator MTTR;
- kalkulator MTBF;
- downtime calculator;
- QR asset generator.

Field form:

- text;
- email;
- phone;
- number;
- select;
- multi-select;
- checkbox;
- radio;
- textarea;
- hidden UTM;
- consent.

Form config:

- success message;
- redirect;
- source;
- campaign;
- tags;
- automation trigger;
- downloadable asset;
- webhook.

Anti-spam:

- honeypot;
- rate limit;
- optional CAPTCHA;
- anomaly detection seperlunya.

---

# 10. Demo Management

Dashboard dapat mengatur:

- demo enabled;
- demo dataset;
- reset interval;
- visible modules;
- restricted features;
- CTA;
- max session.

Track:

- demo started;
- feature opened;
- asset viewed;
- work order created;
- QR viewed;
- preventive viewed;
- demo completed;
- CTA clicked.

Demo reset menggunakan scheduled job.

---

# 11. Trial Management

Terintegrasi dengan domain Langganan.

Configurable:

- durasi;
- paket;
- card required atau tidak;
- max user;
- max lokasi;
- max aset;
- grace period;
- extension policy.

Activation checklist:

```text
[ ] Organisasi dibuat
[ ] Lokasi dibuat
[ ] Aset pertama ditambahkan
[ ] User/teknisi diundang
[ ] Work order pertama dibuat
[ ] Preventive pertama dibuat
```

State:

```text
TERDAFTAR
→ SETUP
→ AKTIF
→ TERAKTIVASI
→ KONVERSI
```

Alternative:

```text
KADALUARSA
DIBATALKAN
DIPERPANJANG
```

---

# 12. Campaign Management

Field:

- nama;
- kode;
- objective;
- channel;
- budget;
- mulai;
- selesai;
- audience;
- landing page;
- form;
- offer;
- UTM;
- status.

Status:

```text
DRAF
→ SIAP
→ AKTIF
→ DIJEDA
→ SELESAI
→ DIARSIPKAN
```

Channel:

- Google Ads;
- Meta Ads;
- LinkedIn;
- TikTok;
- YouTube;
- email;
- WhatsApp;
- organic;
- referral;
- partner;
- direct;
- custom.

Objective:

- traffic;
- lead;
- demo;
- trial;
- activation;
- subscription;
- retention;
- referral.

---

# 13. UTM & Attribution

Capture:

- utm_source;
- utm_medium;
- utm_campaign;
- utm_term;
- utm_content;
- referrer;
- landing URL;
- first page;
- session ID;
- device.

Minimum attribution:

- first touch;
- last touch.

Anonymous visitor dapat di-merge setelah form submit/login/trial register. Jangan melakukan identity merge berdasarkan sinyal lemah.

---

# 14. Email Marketing

Template:

- nurturing;
- onboarding;
- trial;
- lifecycle;
- reactivation;
- newsletter;
- referral;
- partner.

Variable:

```text
{{Nama}}
{{NamaPerusahaan}}
{{Industri}}
{{HariTrialTersisa}}
{{LinkDashboard}}
{{LinkDemo}}
{{LinkHarga}}
```

Contoh trial sequence:

```text
H0 Welcome
H1 Tambah Aset
H2 QR Asset
H3 Work Order
H5 Preventive
H7 Dashboard
H10 Case Study
H12 Reminder Trial
H14 Upgrade
H17 Feedback
H21 Reactivation
```

Status:

```text
TERJADWAL
DIKIRIM
TERKIRIM
DIBUKA
DIKLIK
BOUNCE
GAGAL
UNSUBSCRIBE
```

Provider menggunakan adapter.

---

# 15. WhatsApp Automation

Provider abstraction wajib.

Flow contoh:

```text
Halo, saya asisten Amanpoll.

1. Lihat Demo
2. Coba Gratis
3. Harga
4. Asset Management
5. Maintenance
6. Sparepart
7. Enterprise
```

Semua menu dan respons configurable.

Wajib hormati:

- opt-in;
- template approval provider;
- frequency cap;
- unsubscribe/STOP;
- suppression list.

---

# 16. Marketing Automation Engine

Struktur:

```text
Trigger
→ Condition
→ Delay
→ Action
→ Condition
→ Action
```

Trigger contoh:

- ProspekDibuat;
- FormulirDikirim;
- DemoDimulai;
- DemoSelesai;
- TrialDimulai;
- AsetPertamaDibuat;
- PerintahKerjaPertamaDibuat;
- TrialTeraktivasi;
- TrialAkanBerakhir;
- TrialBerakhir;
- LanggananAktif;
- LanggananDibatalkan;
- PembayaranGagal;
- LeadTidakAktif;
- HalamanHargaDilihat;
- ReferralTerdaftar;
- PartnerMengirimLead.

Condition:

- industri;
- source;
- campaign;
- score;
- paket;
- activity;
- trial status;
- asset count;
- location count;
- tag;
- consent;
- subscription state.

Action:

- kirim email;
- kirim WhatsApp;
- tambah tag;
- update score;
- update status;
- buat task;
- notification;
- webhook;
- pindah pipeline;
- enroll sequence;
- remove sequence;
- create coupon;
- extend trial jika policy membolehkan;
- notify founder;
- export audience.

Setiap execution wajib idempotent.

---

# 17. Social Media Scheduler

Support abstraction:

- LinkedIn;
- Instagram;
- Facebook;
- TikTok;
- YouTube;
- X bila dibutuhkan.

Provider dapat memakai Metricool atau adapter lain.

Data:

- channel;
- caption;
- media;
- schedule;
- campaign;
- CTA;
- UTM;
- status.

State:

```text
DRAF
→ REVIEW
→ TERJADWAL
→ DIPROSES
→ TERBIT
→ GAGAL
```

Satu konten utama dapat mempunyai banyak distribusi:

```text
Artikel
├── LinkedIn
├── Instagram
├── Reels
├── TikTok
├── Shorts
└── Email
```

---

# 18. Pricing & Offer

Source harga tetap dari domain Langganan/Billing.

Marketing hanya mengatur:

- urutan paket;
- highlight;
- CTA;
- comparison;
- FAQ;
- badge;
- promo presentation.

Offer:

- coupon;
- trial extension;
- onboarding credit;
- referral reward;
- partner discount;
- seasonal campaign.

Jangan mengubah transaksi Billing secara langsung dari domain Marketing.

---

# 19. Referral

Customer mempunyai:

- referral code;
- referral URL;
- click;
- lead;
- trial;
- paid;
- reward.

State:

```text
DIBUAT
→ DIKLIK
→ LEAD
→ TRIAL
→ PAID
→ REWARD_PENDING
→ REWARDED
```

Reward configurable:

- extension;
- credit;
- coupon;
- custom.

Wajib anti self-referral.

---

# 20. Partner Program

Jenis:

- consultant;
- software house;
- vendor maintenance;
- vendor calibration;
- system integrator;
- IT consultant;
- reseller;
- affiliate.

Data:

- perusahaan;
- PIC;
- contact;
- type;
- commission rule;
- status;
- referral code;
- agreement reference;
- payout reference.

Tahap lanjut:

```text
partner.amanpoll.com
```

Partner dapat melihat lead, trial, paid customer, commission, payout, dan marketing material.

---

# 21. Eksperimen A/B

Target:

- headline;
- CTA;
- landing section;
- form length;
- pricing presentation;
- onboarding copy;
- email subject.

State:

```text
DRAF
→ AKTIF
→ DIJEDA
→ SELESAI
```

Metric:

- CTR;
- form conversion;
- demo conversion;
- trial conversion;
- activation;
- paid conversion.

Jangan auto-declare winner tanpa minimum sample configuration.

---

# 22. Event Taxonomy

Public:

```text
HalamanDilihat
CTADiklik
FormulirDimulai
FormulirDikirim
DemoDimulai
DemoSelesai
HargaDilihat
ArtikelDilihat
TemplateDiunduh
```

Trial:

```text
TrialDimulai
LokasiPertamaDibuat
AsetPertamaDibuat
PenggunaPertamaDiundang
PerintahKerjaPertamaDibuat
PreventivePertamaDibuat
TrialTeraktivasi
```

Revenue:

```text
CheckoutDimulai
LanggananDibuat
PembayaranBerhasil
PembayaranGagal
UpgradeDilakukan
DowngradeDilakukan
LanggananDibatalkan
```

Referral/Partner:

```text
ReferralDiklik
ReferralMenjadiLead
ReferralMenjadiTrial
ReferralMenjadiPaid
PartnerMengirimLead
KomisiPartnerDibuat
```

---

# 23. Database Konseptual

Gunakan naming PascalCase konsisten dengan Amanpoll.

```text
Prospek
KontakProspek
OrganisasiProspek
AktivitasProspek
TahapPipeline
RiwayatTahapProspek
SkorProspek
AturanSkorProspek
TagProspek
ProspekTag

Kampanye
KampanyeChannel
KampanyeBiaya
KampanyeTarget
KampanyeKonten

HalamanPemasaran
VersiHalamanPemasaran
BlokHalamanPemasaran
RedirectPemasaran

FormulirPemasaran
FieldFormulirPemasaran
PengirimanFormulir

KontenPemasaran
VersiKontenPemasaran
KeywordSeo
ClusterSeo
KontenKeywordSeo

SesiPengunjung
EventPemasaran
AttributionPemasaran
UtmPemasaran

DemoPemasaran
SesiDemo
EventDemo

OtomasiPemasaran
VersiOtomasiPemasaran
LangkahOtomasiPemasaran
EksekusiOtomasiPemasaran
LogEksekusiOtomasi

TemplateEmailPemasaran
SequenceEmailPemasaran
LangkahSequenceEmail
PengirimanEmailPemasaran

TemplateWhatsAppPemasaran
PengirimanWhatsAppPemasaran

KontenSosial
DistribusiKontenSosial
JadwalKontenSosial

ProgramReferral
Referral
RewardReferral

ProgramPartner
Partner
LeadPartner
AturanKomisiPartner
KomisiPartner
PayoutPartner

EksperimenPemasaran
VarianEksperimen
PartisipasiEksperimen
HasilEksperimen

KonfigurasiPemasaran
```

Jangan membuat tabel baru jika fungsi setara sudah tersedia di domain lain.

Gunakan ULID sesuai konvensi project.

---

# 24. Relasi dengan Domain Existing

## Langganan/Billing

Marketing boleh membaca:

- paket;
- pricing;
- trial;
- subscription;
- payment state.

Mutation harus lewat application service/domain contract.

## Organisasi

Saat trial menghasilkan workspace:

```text
Prospek
→ Organisasi
```

Reference attribution tetap dipertahankan.

## Notifikasi

Gunakan engine Notifikasi untuk internal alert.

## Integrasi

Gunakan webhook, API, outbox, idempotency.

## Audit

Semua publication/configuration/action sensitif tercatat.

---

# 25. Permission

Minimal:

```text
platform.pemasaran.lihat
platform.pemasaran.kelola

platform.prospek.lihat
platform.prospek.kelola
platform.prospek.ekspor

platform.kampanye.lihat
platform.kampanye.kelola

platform.halaman.lihat
platform.halaman.kelola
platform.halaman.terbitkan

platform.konten.lihat
platform.konten.kelola
platform.konten.terbitkan

platform.otomasi.lihat
platform.otomasi.kelola
platform.otomasi.aktifkan

platform.email.lihat
platform.email.kelola

platform.whatsapp.lihat
platform.whatsapp.kelola

platform.referral.lihat
platform.referral.kelola

platform.partner.lihat
platform.partner.kelola

platform.analytics.lihat
platform.eksperimen.kelola
```

Export data harus permission terpisah.

---

# 26. Consent, Audit, dan Safety

Wajib simpan:

- consent marketing;
- timestamp;
- consent source;
- policy version;
- unsubscribe;
- suppression list;
- deletion/anonymization request.

Audit:

- publish landing page;
- activate automation;
- edit trial config;
- change pricing presentation;
- issue coupon;
- change referral reward;
- approve commission;
- export lead;
- delete/anonymize lead;
- publish social content.

Rate limit:

- form;
- demo;
- public tool;
- email;
- WhatsApp;
- webhook;
- export.

Automation mempunyai:

- execution cap;
- daily message cap;
- retry cap;
- DLQ;
- provider failure handling.

---

# 27. Provider Contract

Contoh:

```php
interface PenyediaEmailPemasaran
{
    public function kirim(PesanEmail $pesan): HasilPengiriman;
}

interface PenyediaWhatsAppPemasaran
{
    public function kirim(PesanWhatsApp $pesan): HasilPengiriman;
}

interface PenyediaPublikasiSosial
{
    public function jadwalkan(KontenSosial $konten): HasilPublikasi;
}

interface PenyediaAnalyticsPemasaran
{
    public function catat(EventPemasaran $event): void;
}
```

---

# 28. Background Job

```text
ProsesOtomasiPemasaran
KirimEmailPemasaran
KirimWhatsAppPemasaran
PublikasikanKontenSosial
HitungSkorProspek
HitungAttribution
ResetDemo
SinkronkanStatusProvider
HitungMetrikKampanye
ProsesRewardReferral
HitungKomisiPartner
```

Gunakan database queue.

Cron awal:

```bash
php artisan schedule:run
php artisan queue:work database --stop-when-empty --max-time=50
```

---

# 29. Developer Settings

```text
Growth & Marketing
└── Pengaturan
    ├── General
    ├── Domain
    ├── SEO
    ├── Analytics
    ├── Email Provider
    ├── WhatsApp Provider
    ├── Social Provider
    ├── Consent
    ├── Lead Scoring
    ├── Trial
    ├── Attribution
    ├── Referral
    └── Partner
```

Dashboard tidak pernah menampilkan secret penuh.

---

# 30. Feature Flag

```text
marketing.crm
marketing.cms
marketing.automation
marketing.email
marketing.whatsapp
marketing.social
marketing.referral
marketing.partner
marketing.experiment
marketing.analytics
```

---

# 31. MVP

Prioritas pertama:

```text
1. Prospek + CRM
2. UTM + first/last-touch attribution
3. Landing page configurable
4. Form builder sederhana
5. Trial event tracking
6. Email sequence
7. Automation dasar
8. Growth dashboard
9. Referral dasar
10. Audit + consent
```

Setelah stabil:

```text
11. WhatsApp automation
12. Social scheduler
13. Partner program
14. A/B testing
15. SEO manager
16. Lead magnet/tools
17. Advanced attribution
```

---

# 32. Integrasi ke TASK.md

Jangan membuat implementasi marketing meloncat melewati dependency utama.

Setelah Foundation + IAM:

- permission marketing;
- config;
- event collector;
- UTM;
- basic lead capture.

Setelah Notifikasi + Integrasi:

- email adapter;
- WhatsApp adapter;
- webhook;
- automation.

Setelah Langganan:

- trial lifecycle;
- pricing presentation;
- conversion tracking;
- revenue attribution;
- referral reward.

Setelah UI Core:

- Growth dashboard;
- CRM;
- page builder;
- campaign UI.

Setelah Hardening:

- consent enforcement;
- export security;
- suppression list;
- retry policy;
- audit verification.

---

# 33. Public Route

```text
/
 /fitur
 /fitur/asset-management
 /fitur/work-order
 /fitur/preventive-maintenance
 /fitur/inventory
 /fitur/calibration
 /fitur/procurement

 /industri/manufaktur
 /industri/hotel
 /industri/property
 /industri/healthcare
 /industri/workshop

 /harga
 /demo
 /trial

 /artikel
 /artikel/{slug}

 /template
 /tools
```

---

# 34. Acceptance Criteria MVP

MVP selesai jika:

- landing page dapat dibuat dari dashboard;
- form public menghasilkan lead;
- UTM tersimpan;
- first touch tidak tertimpa;
- last touch dapat diperbarui;
- lead mempunyai pipeline;
- lead score configurable;
- trial event muncul di timeline;
- automation dapat dipicu event;
- email sequence berjalan;
- retry tidak menghasilkan duplicate send;
- trial conversion terhubung ke Langganan;
- dashboard menunjukkan funnel;
- referral dapat ditrack;
- unsubscribe menghentikan marketing message;
- action sensitif masuk audit;
- seluruh UI responsive;
- job berjalan dengan database queue;
- test tersedia.

---

# 35. Test Minimum

Feature:

```text
ProspekDibuatTest
UtmTersimpanTest
AttributionPertamaTerjagaTest
AttributionTerakhirDiperbaruiTest
PipelineProspekTest
SkorProspekTest
FormulirPemasaranTest
AutomationTriggerTest
AutomationIdempotencyTest
SequenceEmailTest
SuppressionListTest
TrialActivationTest
ReferralConversionTest
PermissionPemasaranTest
AuditPemasaranTest
```

Unit:

```text
HitungSkorProspekTest
EvaluasiKondisiOtomasiTest
HitungRewardReferralTest
HitungAttributionTest
TransisiStatusProspekTest
```

---

# 36. Definition of Done

Fitur marketing dianggap selesai jika:

- domain rule jelas;
- authorization ada;
- audit ada bila diperlukan;
- async action idempotent;
- UI responsive;
- error state tersedia;
- empty state tersedia;
- test lulus;
- tidak ada N+1;
- tidak ada secret di source;
- tidak ada business logic besar di controller;
- dokumentasi diperbarui;
- `PROGRESS.md` diperbarui;
- commit dibuat secara logis.

---

# 37. Hasil Akhir yang Diinginkan

Founder harus dapat membuka satu dashboard dan melihat:

```text
Hari Ini
├── 1.240 visitor
├── 64 lead
├── 21 demo
├── 14 trial
├── 8 activated
├── 3 paid
└── RpXX MRR baru
```

Dashboard harus dapat menjawab:

- channel mana menghasilkan customer;
- campaign mana menghasilkan revenue;
- landing page mana paling efektif;
- industri mana paling kuat;
- trial mana high-intent;
- automation mana gagal;
- referral mana convert;
- partner mana menghasilkan revenue.

Tujuan akhirnya bukan hanya "menu marketing", tetapi **mesin pemasaran dan pertumbuhan Amanpoll yang terintegrasi penuh dengan produk dan dapat dikendalikan dari Dashboard Platform**.
