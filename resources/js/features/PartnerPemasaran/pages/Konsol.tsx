import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';

type Program = {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  HariAtribusi: number;
  Aktif: boolean;
  JumlahPartner: number;
};

type Partner = {
  Id: string;
  Kode: string;
  NamaPerusahaan: string;
  Jenis: string;
  LabelJenis: string;
  NamaPic: string;
  EmailPic: string;
  TeleponPic: string | null;
  Status: string;
  Program: string | null;
  ProgramPartnerId: string;
  ReferensiPerjanjian: string | null;
  ReferensiPayout: string | null;
  TerakhirMasukPada: string | null;
};

type Aturan = {
  Id: string;
  Nama: string;
  Program: string | null;
  ProgramPartnerId: string;
  PartnerId: string | null;
  Partner: string | null;
  Jenis: string;
  LabelJenis: string;
  Nilai: number;
  MaksPembayaran: number | null;
  Aktif: boolean;
};

type Lead = {
  Id: string;
  Partner: string;
  NamaPerusahaan: string;
  NamaKontak: string;
  Email: string;
  Telepon: string | null;
  Catatan: string | null;
  Status: string;
  AlasanDitolak: string | null;
  DikirimPada: string;
};

type Komisi = {
  Id: string;
  Partner: string;
  Lead: string;
  JumlahPembayaran: number;
  Jumlah: number;
  Status: string;
  PayoutPartnerId: string | null;
  DibuatPada: string;
};

type Payout = {
  Id: string;
  Partner: string;
  Nomor: string;
  Jumlah: number;
  JumlahKomisi: number;
  Status: string;
  ReferensiPembayaran: string | null;
  Catatan: string | null;
  DibayarPada: string | null;
};

type Pilihan = { Jenis: string[]; StatusPartner: string[]; JenisKomisi: string[] };

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

const AKAR = '/admin-platform/pemasaran/partner';

const rupiah = (nilai: number) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(nilai);

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
        className="mb-6"
      />

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {Object.entries(ringkasanKomisi).map(([status, jumlah]) => (
          <Card key={status}>
            <CardContent className="pt-6">
              <p className="text-sm text-muted-foreground">Komisi {status}</p>
              <p className="text-lg font-semibold text-foreground">{jumlah}</p>
            </CardContent>
          </Card>
        ))}
      </div>

      <Tabs defaultValue="partner" className="mt-6">
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
          {partner.length === 0 && <p className="text-sm text-muted-foreground">Belum ada partner terdaftar.</p>}
          {partner.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row items-start justify-between gap-3">
                <div>
                  <CardTitle className="text-base">{satu.NamaPerusahaan}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    {satu.LabelJenis} · Kode {satu.Kode} · {satu.Program ?? 'Tanpa program'}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    {satu.NamaPic} · {satu.EmailPic}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={satu.Status === 'Aktif' ? 'default' : 'secondary'}>{satu.Status}</Badge>
                  <DialogPartner partner={satu} program={program} pilihan={pilihan} />
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => router.post(`${AKAR}/${satu.Id}/payout`, {}, { preserveScroll: true })}
                  >
                    Susun payout
                  </Button>
                </div>
              </CardHeader>
              <CardContent className="text-sm text-muted-foreground">
                Perjanjian: {satu.ReferensiPerjanjian ?? '—'} · Referensi payout: {satu.ReferensiPayout ?? '—'} ·
                Terakhir masuk: {waktu(satu.TerakhirMasukPada)}
              </CardContent>
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="program" className="mt-4 space-y-3">
          {program.length === 0 && <p className="text-sm text-muted-foreground">Belum ada program partner.</p>}
          {program.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row items-start justify-between gap-3">
                <div>
                  <CardTitle className="text-base">{satu.Nama}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    Kode {satu.Kode} · atribusi {satu.HariAtribusi} hari · {satu.JumlahPartner} partner
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={satu.Aktif ? 'default' : 'secondary'}>{satu.Aktif ? 'Aktif' : 'Nonaktif'}</Badge>
                  <DialogProgram program={satu} />
                </div>
              </CardHeader>
              {satu.Keterangan && (
                <CardContent className="text-sm text-muted-foreground">{satu.Keterangan}</CardContent>
              )}
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="aturan" className="mt-4 space-y-3">
          <DialogAturan aturan={null} program={program} partner={partner} pilihan={pilihan} />
          {aturan.length === 0 && <p className="text-sm text-muted-foreground">Belum ada aturan komisi.</p>}
          {aturan.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row items-start justify-between gap-3">
                <div>
                  <CardTitle className="text-base">{satu.Nama}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    {satu.LabelJenis} · {satu.Jenis === 'Persentase' ? `${satu.Nilai}%` : rupiah(satu.Nilai)} ·{' '}
                    {satu.Partner ? `khusus ${satu.Partner}` : `bawaan ${satu.Program ?? 'program'}`}
                    {satu.MaksPembayaran ? ` · maksimal ${satu.MaksPembayaran} pembayaran` : ''}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={satu.Aktif ? 'default' : 'secondary'}>{satu.Aktif ? 'Aktif' : 'Nonaktif'}</Badge>
                  <DialogAturan aturan={satu} program={program} partner={partner} pilihan={pilihan} />
                </div>
              </CardHeader>
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="lead" className="mt-4 space-y-3">
          {lead.length === 0 && <p className="text-sm text-muted-foreground">Belum ada lead kiriman partner.</p>}
          {lead.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row items-start justify-between gap-3">
                <div>
                  <CardTitle className="text-base">{satu.NamaPerusahaan}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    {satu.NamaKontak} · {satu.Email} · dari {satu.Partner}
                  </p>
                  <p className="text-sm text-muted-foreground">Dikirim {waktu(satu.DikirimPada)}</p>
                  {satu.AlasanDitolak && (
                    <p className="text-sm text-muted-foreground">Ditolak: {satu.AlasanDitolak}</p>
                  )}
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={satu.Status === 'Ditolak' ? 'secondary' : 'default'}>{satu.Status}</Badge>
                  {satu.Status === 'Dikirim' && (
                    <Button
                      size="sm"
                      onClick={() => router.post(`${AKAR}/lead/${satu.Id}/terima`, {}, { preserveScroll: true })}
                    >
                      Terima
                    </Button>
                  )}
                  {satu.Status !== 'Ditolak' && satu.Status !== 'Paid' && (
                    <DialogAlasan
                      judul="Tolak lead"
                      tombol="Tolak"
                      url={`${AKAR}/lead/${satu.Id}/tolak`}
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
              <CardHeader className="flex flex-row items-start justify-between gap-3">
                <div>
                  <CardTitle className="text-base">
                    {rupiah(satu.Jumlah)} untuk {satu.Partner}
                  </CardTitle>
                  <p className="text-sm text-muted-foreground">
                    Lead {satu.Lead} · pembayaran {rupiah(satu.JumlahPembayaran)} · {waktu(satu.DibuatPada)}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={satu.Status === 'Dibatalkan' ? 'secondary' : 'default'}>{satu.Status}</Badge>
                  {satu.Status === 'Tertunda' && (
                    <Button
                      size="sm"
                      onClick={() => router.post(`${AKAR}/komisi/${satu.Id}/setujui`, {}, { preserveScroll: true })}
                    >
                      Setujui
                    </Button>
                  )}
                  {satu.Status !== 'Dibayar' && satu.Status !== 'Dibatalkan' && (
                    <DialogAlasan
                      judul="Batalkan komisi"
                      tombol="Batalkan"
                      url={`${AKAR}/komisi/${satu.Id}/batalkan`}
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
              <CardHeader className="flex flex-row items-start justify-between gap-3">
                <div>
                  <CardTitle className="text-base">
                    {satu.Nomor} · {rupiah(satu.Jumlah)}
                  </CardTitle>
                  <p className="text-sm text-muted-foreground">
                    {satu.Partner} · {satu.JumlahKomisi} komisi · dibayar {waktu(satu.DibayarPada)}
                  </p>
                  {satu.ReferensiPembayaran && (
                    <p className="text-sm text-muted-foreground">Referensi: {satu.ReferensiPembayaran}</p>
                  )}
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={satu.Status === 'Dibayar' ? 'default' : 'secondary'}>{satu.Status}</Badge>
                  {satu.Status !== 'Dibayar' && <DialogBayar payout={satu} />}
                  {satu.Status !== 'Dibayar' && (
                    <DialogAlasan
                      judul="Batalkan payout"
                      tombol="Batalkan"
                      url={`${AKAR}/payout/${satu.Id}/batalkan`}
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

function DialogProgram({ program }: { program: Program | null }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: program?.Kode ?? '',
    Nama: program?.Nama ?? '',
    Keterangan: program?.Keterangan ?? '',
    HariAtribusi: program?.HariAtribusi ?? 180,
    Aktif: program?.Aktif ?? false,
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    const selesai = { preserveScroll: true, onSuccess: () => setBuka(false) };

    if (program) {
      form.put(`${AKAR}/program/${program.Kode}`, selesai);
    } else {
      form.post(`${AKAR}/program`, selesai);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant={program ? 'outline' : 'default'}>
          {program ? 'Ubah' : 'Program baru'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{program ? 'Ubah program partner' : 'Program partner baru'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={kirim} className="space-y-4">
          <Bidang label="Kode" galat={form.errors.Kode}>
            <Input value={form.data.Kode} onChange={(e) => form.setData('Kode', e.target.value)} />
          </Bidang>
          <Bidang label="Nama" galat={form.errors.Nama}>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
          </Bidang>
          <Bidang label="Keterangan" galat={form.errors.Keterangan}>
            <Textarea
              value={form.data.Keterangan}
              onChange={(e) => form.setData('Keterangan', e.target.value)}
            />
          </Bidang>
          <Bidang label="Hari atribusi" galat={form.errors.HariAtribusi}>
            <Input
              type="number"
              value={form.data.HariAtribusi}
              onChange={(e) => form.setData('HariAtribusi', Number(e.target.value))}
            />
          </Bidang>
          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={form.data.Aktif}
              onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
            />
            Aktif
          </label>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogPartner({
  partner,
  program,
  pilihan,
}: {
  partner: Partner | null;
  program: Program[];
  pilihan: Pilihan;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    ProgramPartnerId: partner?.ProgramPartnerId ?? program[0]?.Id ?? '',
    NamaPerusahaan: partner?.NamaPerusahaan ?? '',
    Jenis: partner?.Jenis ?? pilihan.Jenis[0] ?? '',
    NamaPic: partner?.NamaPic ?? '',
    EmailPic: partner?.EmailPic ?? '',
    TeleponPic: partner?.TeleponPic ?? '',
    Status: partner?.Status ?? 'Diajukan',
    ReferensiPerjanjian: partner?.ReferensiPerjanjian ?? '',
    ReferensiPayout: partner?.ReferensiPayout ?? '',
    KataSandi: '',
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    const selesai = {
      preserveScroll: true,
      onSuccess: () => {
        form.reset('KataSandi');
        setBuka(false);
      },
    };

    if (partner) {
      form.put(`${AKAR}/${partner.Id}`, selesai);
    } else {
      form.post(AKAR, selesai);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant={partner ? 'outline' : 'default'}>
          {partner ? 'Ubah' : 'Partner baru'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{partner ? 'Ubah partner' : 'Partner baru'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={kirim} className="space-y-4">
          <Bidang label="Program" galat={form.errors.ProgramPartnerId}>
            <Select
              value={form.data.ProgramPartnerId}
              onValueChange={(nilai) => form.setData('ProgramPartnerId', nilai)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Pilih program" />
              </SelectTrigger>
              <SelectContent>
                {program.map((satu) => (
                  <SelectItem key={satu.Id} value={satu.Id}>
                    {satu.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang label="Nama perusahaan" galat={form.errors.NamaPerusahaan}>
            <Input
              value={form.data.NamaPerusahaan}
              onChange={(e) => form.setData('NamaPerusahaan', e.target.value)}
            />
          </Bidang>
          <Bidang label="Jenis" galat={form.errors.Jenis}>
            <Select value={form.data.Jenis} onValueChange={(nilai) => form.setData('Jenis', nilai)}>
              <SelectTrigger>
                <SelectValue placeholder="Pilih jenis" />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Jenis.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang label="Nama PIC" galat={form.errors.NamaPic}>
            <Input value={form.data.NamaPic} onChange={(e) => form.setData('NamaPic', e.target.value)} />
          </Bidang>
          <Bidang label="Email PIC" galat={form.errors.EmailPic}>
            <Input
              type="email"
              value={form.data.EmailPic}
              onChange={(e) => form.setData('EmailPic', e.target.value)}
            />
          </Bidang>
          <Bidang label="Telepon PIC" galat={form.errors.TeleponPic}>
            <Input value={form.data.TeleponPic} onChange={(e) => form.setData('TeleponPic', e.target.value)} />
          </Bidang>
          <Bidang label="Status" galat={form.errors.Status}>
            <Select value={form.data.Status} onValueChange={(nilai) => form.setData('Status', nilai)}>
              <SelectTrigger>
                <SelectValue placeholder="Pilih status" />
              </SelectTrigger>
              <SelectContent>
                {pilihan.StatusPartner.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang label="Referensi perjanjian" galat={form.errors.ReferensiPerjanjian}>
            <Input
              value={form.data.ReferensiPerjanjian}
              onChange={(e) => form.setData('ReferensiPerjanjian', e.target.value)}
            />
          </Bidang>
          <Bidang label="Referensi payout" galat={form.errors.ReferensiPayout}>
            <Input
              value={form.data.ReferensiPayout}
              onChange={(e) => form.setData('ReferensiPayout', e.target.value)}
            />
          </Bidang>
          <Bidang
            label={partner ? 'Kata sandi baru (kosongkan bila tidak diubah)' : 'Kata sandi'}
            galat={form.errors.KataSandi}
          >
            <Input
              type="password"
              autoComplete="new-password"
              value={form.data.KataSandi}
              onChange={(e) => form.setData('KataSandi', e.target.value)}
            />
          </Bidang>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogAturan({
  aturan,
  program,
  partner,
  pilihan,
}: {
  aturan: Aturan | null;
  program: Program[];
  partner: Partner[];
  pilihan: Pilihan;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    ProgramPartnerId: aturan?.ProgramPartnerId ?? program[0]?.Id ?? '',
    PartnerId: aturan?.PartnerId ?? '',
    Nama: aturan?.Nama ?? '',
    Jenis: aturan?.Jenis ?? pilihan.JenisKomisi[0] ?? '',
    Nilai: aturan?.Nilai ?? 10,
    MaksPembayaran: aturan?.MaksPembayaran ?? '',
    Aktif: aturan?.Aktif ?? true,
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    const selesai = { preserveScroll: true, onSuccess: () => setBuka(false) };

    if (aturan) {
      form.put(`${AKAR}/aturan/${aturan.Id}`, selesai);
    } else {
      form.post(`${AKAR}/aturan`, selesai);
    }
  };

  const seprogram = partner.filter((satu) => satu.ProgramPartnerId === form.data.ProgramPartnerId);

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant={aturan ? 'outline' : 'default'}>
          {aturan ? 'Ubah' : 'Aturan baru'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{aturan ? 'Ubah aturan komisi' : 'Aturan komisi baru'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={kirim} className="space-y-4">
          <Bidang label="Program" galat={form.errors.ProgramPartnerId}>
            <Select
              value={form.data.ProgramPartnerId}
              onValueChange={(nilai) => form.setData('ProgramPartnerId', nilai)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Pilih program" />
              </SelectTrigger>
              <SelectContent>
                {program.map((satu) => (
                  <SelectItem key={satu.Id} value={satu.Id}>
                    {satu.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang label="Khusus partner (kosong = bawaan program)" galat={form.errors.PartnerId}>
            <Select
              value={form.data.PartnerId === '' ? 'bawaan' : form.data.PartnerId}
              onValueChange={(nilai) => form.setData('PartnerId', nilai === 'bawaan' ? '' : nilai)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Bawaan program" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="bawaan">Bawaan program</SelectItem>
                {seprogram.map((satu) => (
                  <SelectItem key={satu.Id} value={satu.Id}>
                    {satu.NamaPerusahaan}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang label="Nama" galat={form.errors.Nama}>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
          </Bidang>
          <Bidang label="Jenis" galat={form.errors.Jenis}>
            <Select value={form.data.Jenis} onValueChange={(nilai) => form.setData('Jenis', nilai)}>
              <SelectTrigger>
                <SelectValue placeholder="Pilih jenis" />
              </SelectTrigger>
              <SelectContent>
                {pilihan.JenisKomisi.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang label={form.data.Jenis === 'Persentase' ? 'Nilai (persen)' : 'Nilai (rupiah)'} galat={form.errors.Nilai}>
            <Input
              type="number"
              step="0.01"
              value={form.data.Nilai}
              onChange={(e) => form.setData('Nilai', Number(e.target.value))}
            />
          </Bidang>
          <Bidang label="Maksimal pembayaran (kosong = tanpa batas)" galat={form.errors.MaksPembayaran}>
            <Input
              type="number"
              value={form.data.MaksPembayaran}
              onChange={(e) => form.setData('MaksPembayaran', e.target.value)}
            />
          </Bidang>
          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={form.data.Aktif}
              onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
            />
            Aktif
          </label>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogBayar({ payout }: { payout: Payout }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Referensi: '', Catatan: '' });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.post(`${AKAR}/payout/${payout.Id}/bayar`, {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm">Tandai dibayar</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tandai {payout.Nomor} dibayar</DialogTitle>
        </DialogHeader>
        <form onSubmit={kirim} className="space-y-4">
          <Bidang label="Referensi transfer" galat={form.errors.Referensi}>
            <Input value={form.data.Referensi} onChange={(e) => form.setData('Referensi', e.target.value)} />
          </Bidang>
          <Bidang label="Catatan" galat={form.errors.Catatan}>
            <Textarea value={form.data.Catatan} onChange={(e) => form.setData('Catatan', e.target.value)} />
          </Bidang>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogAlasan({
  judul,
  tombol,
  url,
  maks,
}: {
  judul: string;
  tombol: string;
  url: string;
  maks: number;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Alasan: '' });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.post(url, { preserveScroll: true, onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          {tombol}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{judul}</DialogTitle>
        </DialogHeader>
        <form onSubmit={kirim} className="space-y-4">
          <Bidang label="Alasan" galat={form.errors.Alasan}>
            <Textarea
              maxLength={maks}
              value={form.data.Alasan}
              onChange={(e) => form.setData('Alasan', e.target.value)}
            />
          </Bidang>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function Bidang({
  label,
  galat,
  children,
}: {
  label: string;
  galat?: string;
  children: React.ReactNode;
}) {
  return (
    <div className="space-y-1.5">
      <Label>{label}</Label>
      {children}
      {galat && <p className="text-sm text-destructive">{galat}</p>}
    </div>
  );
}
