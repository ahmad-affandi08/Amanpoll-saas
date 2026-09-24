# Peta Schema Amanpoll

Total tabel: **220**, ditambah 4 view.

Pengelompokan diturunkan dari domain pemilik modelnya (`protected $table` di tiap model),
bukan disusun manual. Tabel yang tidak dimiliki domain mana pun ada di bagian Infrastruktur.

## Platform

- `AdminPlatform`
- `HariLibur`
- `Izin`
- `KategoriLokasi`
- `KonfigurasiOrganisasi`
- `KunciApi`
- `Lokasi`
- `NomorDokumen`
- `Organisasi`
- `Pengguna`
- `PenggunaPeran`
- `Peran`
- `PeranIzin`
- `PerangkatPengguna`
- `UnitOrganisasi`

## Kolaborasi

- `Berkas`
- `DefinisiKolomKustom`
- `EntitasTag`
- `KomentarEntitas`
- `LampiranEntitas`
- `NilaiKolomKustom`
- `Tag`

## Penyedia

- `KategoriPenyedia`
- `KontakPenyedia`
- `PenilaianPenyedia`
- `Penyedia`
- `PenyediaKategori`

## Aset

- `Aset`
- `GaransiAset`
- `KategoriAset`
- `Merek`
- `MeterAset`
- `ModelAset`
- `NilaiAset`
- `PembacaanMeterAset`
- `RelasiAset`
- `RiwayatLokasiAset`
- `RiwayatPenanggungJawabAset`

## SiklusAset

- `DetailMutasiAset`
- `DetailPenghapusanAset`
- `DetailSerahTerimaAset`
- `PengajuanPenghapusanAset`
- `PermintaanMutasiAset`
- `SerahTerimaAset`

## Pemeliharaan

- `AnalisisKegagalan`
- `AturanTingkatLayanan`
- `BiayaPerintahKerja`
- `KategoriKeluhan`
- `Keluhan`
- `KodeKegagalan`
- `KonfirmasiPenerimaPerintahKerja`
- `PenugasanPerintahKerja`
- `PerintahKerja`
- `PerintahKerjaAset`
- `RiwayatStatusKeluhan`
- `RiwayatStatusPerintahKerja`
- `TingkatLayanan`
- `WaktuHentiAset`
- `WaktuKerja`

## PreventifInspeksi

- `ButirTemplatDaftarPeriksa`
- `Inspeksi`
- `JadwalPemeliharaan`
- `JawabanDaftarPeriksa`
- `PelaksanaanDaftarPeriksa`
- `RencanaPemeliharaan`
- `RencanaPemeliharaanAset`
- `TemplatDaftarPeriksa`
- `TemplatInspeksi`

## Kalibrasi

- `HasilTitikUkurKalibrasi`
- `JenisKalibrasi`
- `PelaksanaanKalibrasi`
- `RencanaKalibrasi`
- `TitikUkurKalibrasi`

## Persediaan

- `DetailMutasiStok`
- `Gudang`
- `KategoriSukuCadang`
- `KelompokSukuCadang`
- `KompatibilitasSukuCadang`
- `LokasiGudang`
- `MutasiStok`
- `PemakaianSukuCadang`
- `ReservasiSukuCadang`
- `StokSukuCadang`
- `SukuCadang`

## PerencanaanPengadaan

- `Anggaran`
- `DetailPenawaranPenyedia`
- `DetailPenerimaanPembelian`
- `DetailPermintaanPembelian`
- `DetailPesananPembelian`
- `DetailRencanaPengadaan`
- `PembayaranPenyedia`
- `PenawaranPenyedia`
- `PenerimaanPembelian`
- `PenilaianUsulanAset`
- `PenyediaPermintaanPenawaran`
- `PermintaanPembelian`
- `PermintaanPenawaran`
- `PesananPembelian`
- `PosAnggaran`
- `RencanaPengadaan`
- `TagihanPenyedia`
- `TransaksiAnggaran`
- `UsulanAset`

## Kontrak

- `Kontrak`
- `KontrakAset`
- `LayananKontrak`

## Kepatuhan

- `IntegrasiEksternal`
- `KepatuhanAset`
- `PemetaanDataEksternal`
- `PersyaratanKepatuhan`
- `SertifikasiAset`
- `SinkronisasiEksternal`
- `StandarKepatuhan`

## Aspak

- `AlkesAspak`
- `PemetaanAspak`

## Kodefikasi

- `KodeBarang`
- `KodeBarangAset`

## Persetujuan

- `AlurPersetujuan`
- `KeputusanPersetujuan`
- `PermintaanPersetujuan`
- `TahapPersetujuan`

## Notifikasi

- `EskalasiTingkatLayanan`
- `Notifikasi`
- `PreferensiNotifikasi`
- `TemplatNotifikasi`

## IntegrasiAudit

- `CatatanAkses`
- `CatatanAudit`
- `KotakKeluarPeristiwa`
- `KunciIdempotensi`
- `PanggilanBalikWeb`
- `PengirimanPanggilanBalikWeb`

## Sinkronisasi

- `AntrianSinkronisasi`
- `PenandaSinkronisasi`

## Pelaporan

- `DasborTersimpan`
- `KomponenDasbor`
- `LaporanTersimpan`

## Langganan

- `FiturPaket`
- `Langganan`
- `PaketFitur`
- `PaketLangganan`
- `PembayaranLangganan`
- `TagihanLangganan`

## Pemasaran

- `AktivitasProspek`
- `AlertPemasaran`
- `AttributionPemasaran`
- `AturanKomisiPartner`
- `AturanSkorProspek`
- `BlokHalamanPemasaran`
- `ButirAktivasiTrial`
- `ClusterSeo`
- `DaftarSupresi`
- `DemoPemasaran`
- `DistribusiKontenSosial`
- `EksekusiOtomasiPemasaran`
- `EksperimenPemasaran`
- `EventDemo`
- `EventPemasaran`
- `FieldFormulirPemasaran`
- `FiturPlatform`
- `FormulirPemasaran`
- `HalamanPemasaran`
- `HasilEksperimen`
- `JadwalKontenSosial`
- `Kampanye`
- `KampanyeBiaya`
- `KampanyeChannel`
- `KampanyeKonten`
- `KampanyeTarget`
- `KeywordSeo`
- `KodeReferral`
- `KomisiPartner`
- `KonfigurasiPemasaran`
- `KonsenPemasaran`
- `KontakProspek`
- `KontenKeywordSeo`
- `KontenPemasaran`
- `KontenSosial`
- `LangkahOtomasiPemasaran`
- `LangkahSequenceEmail`
- `LeadPartner`
- `LogEksekusiOtomasi`
- `MenuWhatsAppPemasaran`
- `MetrikKampanye`
- `OrganisasiProspek`
- `OtomasiPemasaran`
- `PartisipasiEksperimen`
- `Partner`
- `PayoutPartner`
- `PendaftaranSequence`
- `PengirimanEmailPemasaran`
- `PengirimanFormulir`
- `PengirimanWhatsAppPemasaran`
- `PermintaanDataProspek`
- `ProgramPartner`
- `ProgramReferral`
- `Prospek`
- `ProspekTag`
- `RedirectPemasaran`
- `Referral`
- `RewardReferral`
- `RiwayatTahapProspek`
- `SequenceEmailPemasaran`
- `SesiDemo`
- `SesiPengunjung`
- `SkorProspek`
- `TagProspek`
- `TahapPipeline`
- `TemplateEmailPemasaran`
- `TemplateWhatsAppPemasaran`
- `Trial`
- `UtmPemasaran`
- `VarianEksperimen`
- `VersiHalamanPemasaran`
- `VersiKontenPemasaran`
- `VersiOtomasiPemasaran`

## Infrastruktur

- `AntrianPekerjaan`
- `KelompokAntrianPekerjaan`
- `PekerjaanGagal`
- `TokenResetKataSandi`
- `UrutanKode`

## View

- `ViewKepatuhanKalibrasi`
- `ViewKinerjaPerintahKerja`
- `ViewRingkasanAset`
- `ViewStokSukuCadang`
