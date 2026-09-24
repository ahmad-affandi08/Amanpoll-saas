import { Head, router } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import { Badge } from '@/components/ui/badge';
import { varianAktif, varianStatus } from '@/features/Pemasaran/status';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { formatUang } from '@/lib/uang';
import type {
  Aturan,
  Komisi,
  Lead,
  Partner,
  Payout,
  Pilihan,
  Program,
} from '@/features/PartnerPemasaran/types';
import { DialogProgram } from '@/features/PartnerPemasaran/components/DialogProgram';
import { DialogPartner } from '@/features/PartnerPemasaran/components/DialogPartner';
import { DialogAturan } from '@/features/PartnerPemasaran/components/DialogAturan';
import { DialogBayar } from '@/features/PartnerPemasaran/components/DialogBayar';
import { DialogAlasan } from '@/features/PartnerPemasaran/components/DialogAlasan';

interface Props {
  program: Program[];
  partner: Partner[];
  aturan: Aturan[];
  lead: Lead[];
  komisi: Komisi[];
  payout: Payout[];
  ringkasanKomisi: Record<string, number>;
  pilihan: Pilihan;
  hostPartner: string | null;
}

const waktu = (nilai: string | null) => (nilai ? new Date(nilai).toLocaleString('id-ID') : '—');

export default function PartnerPemasaranKonsol({
  program,
  partner,
  aturan,
  lead,
  komisi,
  payout,
  ringkasanKomisi,
  pilihan,
  hostPartner,
}: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Partner" />

      <KepalaHalaman
        judul="Partner"
        deskripsi={
          hostPartner
            ? `Program partner, lead kiriman, komisi, dan payout. Portalnya di ${hostPartner}.`
            : 'Program partner, lead kiriman, komisi, dan payout. Host portal partner belum dikonfigurasi.'
        }
        tanpaBreadcrumb
        aksi={<DialogProgram program={null} />}
        className="mb-5"
      />

      <DeretStatistik kolom={4}>
        {Object.entries(ringkasanKomisi).map(([status, jumlah]) => (
          <KartuStatistik key={status} menyatu label={`Komisi ${status}`} nilai={jumlah} />
        ))}
      </DeretStatistik>

      <Tabs defaultValue="partner" className="mt-5">
        <TabsList>
          <TabsTrigger value="partner">Partner</TabsTrigger>
          <TabsTrigger value="program">Program</TabsTrigger>
          <TabsTrigger value="aturan">Aturan komisi</TabsTrigger>
          <TabsTrigger value="lead">Lead</TabsTrigger>
          <TabsTrigger value="komisi">Komisi</TabsTrigger>
          <TabsTrigger value="payout">Payout</TabsTrigger>
        </TabsList>

        <TabsContent value="partner" className="mt-4 space-y-3">
          <DialogPartner partner={null} program={program} pilihan={pilihan} />
          {partner.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada partner terdaftar.</p>
          )}
          {partner.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3 pb-4">
                <div>
                  <CardTitle className="text-[15px]">{satu.NamaPerusahaan}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    {satu.LabelJenis} · Kode {satu.Kode} · {satu.Program ?? 'Tanpa program'}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    {satu.NamaPic} · {satu.EmailPic}
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                  <Badge variant={varianStatus(satu.Status)}>{satu.Status}</Badge>
                  <DialogPartner partner={satu} program={program} pilihan={pilihan} />
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() =>
                      router.post(rutePemasaran.partnerPayoutBaru(satu.Id), {}, { preserveScroll: true })
                    }
                  >
                    Susun payout
                  </Button>
                </div>
              </CardHeader>
              <CardContent className="pt-0 text-sm text-muted-foreground">
                Perjanjian: {satu.ReferensiPerjanjian ?? '—'} · Referensi payout:{' '}
                {satu.ReferensiPayout ?? '—'} · Terakhir masuk: {waktu(satu.TerakhirMasukPada)}
              </CardContent>
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="program" className="mt-4 space-y-3">
          {program.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada program partner.</p>
          )}
          {program.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3 pb-4">
                <div>
                  <CardTitle className="text-[15px]">{satu.Nama}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    Kode {satu.Kode} · atribusi {satu.HariAtribusi} hari · {satu.JumlahPartner} partner
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                  <Badge variant={varianAktif(satu.Aktif)}>{satu.Aktif ? 'Aktif' : 'Nonaktif'}</Badge>
                  <DialogProgram program={satu} />
                </div>
              </CardHeader>
              {satu.Keterangan && (
                <CardContent className="pt-0 text-sm text-muted-foreground">{satu.Keterangan}</CardContent>
              )}
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="aturan" className="mt-4 space-y-3">
          <DialogAturan aturan={null} program={program} partner={partner} pilihan={pilihan} />
          {aturan.length === 0 && <p className="text-sm text-muted-foreground">Belum ada aturan komisi.</p>}
          {aturan.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3 pb-4">
                <div>
                  <CardTitle className="text-[15px]">{satu.Nama}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    {satu.LabelJenis} ·{' '}
                    {satu.Jenis === 'Persentase' ? `${satu.Nilai}%` : formatUang(satu.Nilai)} ·{' '}
                    {satu.Partner ? `khusus ${satu.Partner}` : `bawaan ${satu.Program ?? 'program'}`}
                    {satu.MaksPembayaran ? ` · maksimal ${satu.MaksPembayaran} pembayaran` : ''}
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                  <Badge variant={varianAktif(satu.Aktif)}>{satu.Aktif ? 'Aktif' : 'Nonaktif'}</Badge>
                  <DialogAturan aturan={satu} program={program} partner={partner} pilihan={pilihan} />
                </div>
              </CardHeader>
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="lead" className="mt-4 space-y-3">
          {lead.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada lead kiriman partner.</p>
          )}
          {lead.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3 pb-4">
                <div>
                  <CardTitle className="text-[15px]">{satu.NamaPerusahaan}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    {satu.NamaKontak} · {satu.Email} · dari {satu.Partner}
                  </p>
                  <p className="text-sm text-muted-foreground">Dikirim {waktu(satu.DikirimPada)}</p>
                  {satu.AlasanDitolak && (
                    <p className="text-sm text-muted-foreground">Ditolak: {satu.AlasanDitolak}</p>
                  )}
                </div>
                <div className="flex flex-wrap items-center gap-2">
                  <Badge variant={varianStatus(satu.Status)}>{satu.Status}</Badge>
                  {satu.Status === 'Dikirim' && (
                    <Button
                      size="sm"
                      onClick={() =>
                        router.post(rutePemasaran.partnerLeadTerima(satu.Id), {}, { preserveScroll: true })
                      }
                    >
                      Terima
                    </Button>
                  )}
                  {satu.Status !== 'Ditolak' && satu.Status !== 'Paid' && (
                    <DialogAlasan
                      judul="Tolak lead"
                      tombol="Tolak"
                      url={rutePemasaran.partnerLeadTolak(satu.Id)}
                      maks={300}
                    />
                  )}
                </div>
              </CardHeader>
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="komisi" className="mt-4 space-y-3">
          {komisi.length === 0 && (
            <p className="text-sm text-muted-foreground">
              Komisi lahir dari pembayaran yang benar-benar terjadi; belum ada satu pun.
            </p>
          )}
          {komisi.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3 pb-4">
                <div>
                  <CardTitle className="text-[15px]">
                    {formatUang(satu.Jumlah)} untuk {satu.Partner}
                  </CardTitle>
                  <p className="text-sm text-muted-foreground">
                    Lead {satu.Lead} · pembayaran {formatUang(satu.JumlahPembayaran)} ·{' '}
                    {waktu(satu.DibuatPada)}
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                  <Badge variant={varianStatus(satu.Status)}>{satu.Status}</Badge>
                  {satu.Status === 'Tertunda' && (
                    <Button
                      size="sm"
                      onClick={() =>
                        router.post(rutePemasaran.partnerKomisiSetujui(satu.Id), {}, { preserveScroll: true })
                      }
                    >
                      Setujui
                    </Button>
                  )}
                  {satu.Status !== 'Dibayar' && satu.Status !== 'Dibatalkan' && (
                    <DialogAlasan
                      judul="Batalkan komisi"
                      tombol="Batalkan"
                      url={rutePemasaran.partnerKomisiBatalkan(satu.Id)}
                      maks={500}
                    />
                  )}
                </div>
              </CardHeader>
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="payout" className="mt-4 space-y-3">
          {payout.length === 0 && <p className="text-sm text-muted-foreground">Belum ada payout disusun.</p>}
          {payout.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3 pb-4">
                <div>
                  <CardTitle className="text-[15px]">
                    {satu.Nomor} · {formatUang(satu.Jumlah)}
                  </CardTitle>
                  <p className="text-sm text-muted-foreground">
                    {satu.Partner} · {satu.JumlahKomisi} komisi · dibayar {waktu(satu.DibayarPada)}
                  </p>
                  {satu.ReferensiPembayaran && (
                    <p className="text-sm text-muted-foreground">Referensi: {satu.ReferensiPembayaran}</p>
                  )}
                </div>
                <div className="flex flex-wrap items-center gap-2">
                  <Badge variant={varianStatus(satu.Status)}>{satu.Status}</Badge>
                  {satu.Status !== 'Dibayar' && <DialogBayar payout={satu} />}
                  {satu.Status !== 'Dibayar' && (
                    <DialogAlasan
                      judul="Batalkan payout"
                      tombol="Batalkan"
                      url={rutePemasaran.partnerPayoutBatalkan(satu.Id)}
                      maks={500}
                    />
                  )}
                </div>
              </CardHeader>
            </Card>
          ))}
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}
