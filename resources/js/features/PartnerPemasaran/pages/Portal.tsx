import { FormEvent } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Partner = {
  Kode: string;
  NamaPerusahaan: string;
  Jenis: string;
  LabelJenis: string;
  NamaPic: string;
  Status: string;
  Program: string | null;
  ReferensiPerjanjian: string | null;
  ReferensiPayout: string | null;
};

type Lead = {
  Id: string;
  NamaPerusahaan: string;
  NamaKontak: string;
  Email: string;
  Telepon: string | null;
  Status: string;
  AlasanDitolak: string | null;
  DikirimPada: string;
  MenjadiTrialPada: string | null;
  MenjadiPaidPada: string | null;
};

type Komisi = {
  Id: string;
  Lead: string;
  JumlahPembayaran: number;
  Jumlah: number;
  Status: string;
  DibuatPada: string;
  DibayarPada: string | null;
};

type Payout = {
  Id: string;
  Nomor: string;
  Jumlah: number;
  JumlahKomisi: number;
  Status: string;
  ReferensiPembayaran: string | null;
  DibayarPada: string | null;
};

type Materi = {
  Judul: string;
  Jenis: string;
  Url: string | null;
  TerbitPada: string | null;
};

type Props = {
  partner: Partner;
  ringkasan: Record<string, number>;
  lead: Lead[];
  komisi: Komisi[];
  payout: Payout[];
  materi: Materi[];
};

const rupiah = (nilai: number) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(nilai);

const tanggal = (nilai: string | null) =>
  nilai ? new Date(nilai).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

/** Portal partner: lead, trial, pelanggan berbayar, komisi, payout, dan materi pemasaran. */
export default function PartnerPemasaranPortal({ partner, ringkasan, lead, komisi, payout, materi }: Props) {
  const form = useForm({
    NamaPerusahaan: '',
    NamaKontak: '',
    Email: '',
    Telepon: '',
    Catatan: '',
  });

  const kirimLead = (e: FormEvent) => {
    e.preventDefault();
    form.post('/lead', { preserveScroll: true, onSuccess: () => form.reset() });
  };

  return (
    <div className="min-h-screen bg-permukaan-100 p-6">
      <Head title="Portal Partner" />

      <div className="mx-auto max-w-6xl space-y-6">
        <header className="flex flex-wrap items-start justify-between gap-4 rounded-[10px] border border-border bg-card p-5">
          <div>
            <h1 className="text-xl font-semibold text-foreground">{partner.NamaPerusahaan}</h1>
            <p className="text-sm text-muted-foreground">
              {partner.LabelJenis} · Kode {partner.Kode}
              {partner.Program ? ` · ${partner.Program}` : ''}
            </p>
            <p className="text-sm text-muted-foreground">PIC: {partner.NamaPic}</p>
          </div>
          <Button variant="outline" onClick={() => router.post('/keluar')}>
            Keluar
          </Button>
        </header>

        <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {[
            { label: 'Lead dikirim', nilai: String(ringkasan.Lead ?? 0) },
            { label: 'Menjadi trial', nilai: String(ringkasan.Trial ?? 0) },
            { label: 'Pelanggan berbayar', nilai: String(ringkasan.Berbayar ?? 0) },
            { label: 'Komisi tertunda', nilai: rupiah(ringkasan.KomisiTertunda ?? 0) },
            { label: 'Komisi disetujui', nilai: rupiah(ringkasan.KomisiDisetujui ?? 0) },
            { label: 'Komisi dibayar', nilai: rupiah(ringkasan.KomisiDibayar ?? 0) },
            { label: 'Lead ditolak', nilai: String(ringkasan.Ditolak ?? 0) },
          ].map((kartu) => (
            <div key={kartu.label} className="rounded-[10px] border border-border bg-card p-4">
              <p className="text-sm text-muted-foreground">{kartu.label}</p>
              <p className="text-lg font-semibold text-foreground">{kartu.nilai}</p>
            </div>
          ))}
        </section>

        <section className="rounded-[10px] border border-border bg-card p-5">
          <h2 className="text-base font-semibold text-foreground">Kirim lead baru</h2>
          <form onSubmit={kirimLead} className="mt-4 grid gap-4 md:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="perusahaan">Nama perusahaan</Label>
              <Input
                id="perusahaan"
                value={form.data.NamaPerusahaan}
                onChange={(e) => form.setData('NamaPerusahaan', e.target.value)}
              />
              {form.errors.NamaPerusahaan && (
                <p className="text-sm text-destructive">{form.errors.NamaPerusahaan}</p>
              )}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="kontak">Nama kontak</Label>
              <Input
                id="kontak"
                value={form.data.NamaKontak}
                onChange={(e) => form.setData('NamaKontak', e.target.value)}
              />
              {form.errors.NamaKontak && <p className="text-sm text-destructive">{form.errors.NamaKontak}</p>}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="email-lead">Email</Label>
              <Input
                id="email-lead"
                type="email"
                value={form.data.Email}
                onChange={(e) => form.setData('Email', e.target.value)}
              />
              {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="telepon">Telepon</Label>
              <Input
                id="telepon"
                value={form.data.Telepon}
                onChange={(e) => form.setData('Telepon', e.target.value)}
              />
              {form.errors.Telepon && <p className="text-sm text-destructive">{form.errors.Telepon}</p>}
            </div>

            <div className="space-y-1.5 md:col-span-2">
              <Label htmlFor="catatan">Catatan</Label>
              <Textarea
                id="catatan"
                value={form.data.Catatan}
                onChange={(e) => form.setData('Catatan', e.target.value)}
              />
              {form.errors.Catatan && <p className="text-sm text-destructive">{form.errors.Catatan}</p>}
            </div>

            <div className="md:col-span-2">
              <Button type="submit" disabled={form.processing}>
                Kirim lead
              </Button>
            </div>
          </form>
        </section>

        <section className="rounded-[10px] border border-border bg-card p-5">
          <h2 className="text-base font-semibold text-foreground">Lead Anda</h2>
          {lead.length === 0 ? (
            <p className="mt-3 text-sm text-muted-foreground">Belum ada lead yang dikirim.</p>
          ) : (
            <div className="mt-3 overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="text-muted-foreground">
                  <tr>
                    <th className="py-2 pr-4 font-medium">Perusahaan</th>
                    <th className="py-2 pr-4 font-medium">Kontak</th>
                    <th className="py-2 pr-4 font-medium">Status</th>
                    <th className="py-2 pr-4 font-medium">Dikirim</th>
                    <th className="py-2 font-medium">Berbayar</th>
                  </tr>
                </thead>
                <tbody>
                  {lead.map((satu) => (
                    <tr key={satu.Id} className="border-t border-border">
                      <td className="py-2 pr-4 text-foreground">{satu.NamaPerusahaan}</td>
                      <td className="py-2 pr-4 text-muted-foreground">
                        {satu.NamaKontak}
                        <span className="block text-xs">{satu.Email}</span>
                      </td>
                      <td className="py-2 pr-4 text-foreground">
                        {satu.Status}
                        {satu.AlasanDitolak && (
                          <span className="block text-xs text-muted-foreground">{satu.AlasanDitolak}</span>
                        )}
                      </td>
                      <td className="py-2 pr-4 text-muted-foreground">{tanggal(satu.DikirimPada)}</td>
                      <td className="py-2 text-muted-foreground">{tanggal(satu.MenjadiPaidPada)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>

        <section className="rounded-[10px] border border-border bg-card p-5">
          <h2 className="text-base font-semibold text-foreground">Komisi</h2>
          {komisi.length === 0 ? (
            <p className="mt-3 text-sm text-muted-foreground">
              Komisi lahir dari pembayaran pertama lead Anda; belum ada yang membayar.
            </p>
          ) : (
            <div className="mt-3 overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="text-muted-foreground">
                  <tr>
                    <th className="py-2 pr-4 font-medium">Lead</th>
                    <th className="py-2 pr-4 font-medium">Pembayaran</th>
                    <th className="py-2 pr-4 font-medium">Komisi</th>
                    <th className="py-2 pr-4 font-medium">Status</th>
                    <th className="py-2 font-medium">Dibayar</th>
                  </tr>
                </thead>
                <tbody>
                  {komisi.map((satu) => (
                    <tr key={satu.Id} className="border-t border-border">
                      <td className="py-2 pr-4 text-foreground">{satu.Lead}</td>
                      <td className="py-2 pr-4 text-muted-foreground">{rupiah(satu.JumlahPembayaran)}</td>
                      <td className="py-2 pr-4 text-foreground">{rupiah(satu.Jumlah)}</td>
                      <td className="py-2 pr-4 text-muted-foreground">{satu.Status}</td>
                      <td className="py-2 text-muted-foreground">{tanggal(satu.DibayarPada)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>

        <section className="rounded-[10px] border border-border bg-card p-5">
          <h2 className="text-base font-semibold text-foreground">Payout</h2>
          {payout.length === 0 ? (
            <p className="mt-3 text-sm text-muted-foreground">Belum ada payout yang disusun.</p>
          ) : (
            <ul className="mt-3 space-y-2 text-sm">
              {payout.map((satu) => (
                <li key={satu.Id} className="flex flex-wrap justify-between gap-2 border-t border-border py-2">
                  <span className="text-foreground">
                    {satu.Nomor} · {satu.JumlahKomisi} komisi
                  </span>
                  <span className="text-muted-foreground">
                    {rupiah(satu.Jumlah)} · {satu.Status} · {tanggal(satu.DibayarPada)}
                    {satu.ReferensiPembayaran ? ` · ${satu.ReferensiPembayaran}` : ''}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="rounded-[10px] border border-border bg-card p-5">
          <h2 className="text-base font-semibold text-foreground">Materi pemasaran</h2>
          {materi.length === 0 ? (
            <p className="mt-3 text-sm text-muted-foreground">
              Belum ada materi yang terbit di situs publik.
            </p>
          ) : (
            <ul className="mt-3 space-y-2 text-sm">
              {materi.map((satu) => (
                <li key={satu.Judul} className="flex flex-wrap justify-between gap-2 border-t border-border py-2">
                  <span className="text-foreground">{satu.Judul}</span>
                  <span className="text-muted-foreground">
                    {satu.Jenis} ·{' '}
                    {satu.Url ? (
                      <a className="underline" href={satu.Url} target="_blank" rel="noreferrer">
                        Buka
                      </a>
                    ) : (
                      'Situs publik belum menyala'
                    )}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </section>

        <footer className="pb-4 text-xs text-muted-foreground">
          Perjanjian: {partner.ReferensiPerjanjian ?? '—'} · Referensi payout: {partner.ReferensiPayout ?? '—'}
        </footer>
      </div>
    </div>
  );
}
