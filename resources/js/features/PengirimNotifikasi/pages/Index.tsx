import type { ReactNode } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Mail, MessageCircle, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { tanggalJam } from '@/components/shared/riwayat';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DialogPenyediaLayanan } from '@/features/Platform/components/DialogPenyediaLayanan';
import { rutePengirimNotifikasi } from '@/features/PengirimNotifikasi/api';
import type {
  KategoriPengirim,
  KuotaWhatsApp,
  PenyediaOrganisasi,
} from '@/features/PengirimNotifikasi/types';

interface Props {
  bolehPenyediaSendiri: boolean;
  kategori: KategoriPengirim[];
  kuotaWhatsApp: KuotaWhatsApp;
  platform: { EmailAktif: boolean; WhatsAppAktif: boolean };
  organisasi: { Nama: string; Email: string | null };
  penerimaUji: { Email: string; WhatsApp: string | null };
}

const DESKRIPSI_KATEGORI: Record<KategoriPengirim['Kode'], string> = {
  Email:
    'Alamat pengirim email notifikasi ke staf, mis. ipsrs@rumahsakit.id. Domainnya harus terverifikasi di penyedia.',
  WhatsApp:
    'Nomor pengirim notifikasi WhatsApp ke staf. Pesan lewat nomor sendiri tidak memakan kuota WhatsApp bawaan.',
};

/** Pengantar notifikasi milik organisasi: email dan nomor WhatsApp sendiri (PRD 8.23). */
export default function PengirimNotifikasiIndex({
  bolehPenyediaSendiri,
  kategori,
  kuotaWhatsApp,
  platform,
  organisasi,
  penerimaUji,
}: Props) {
  const aktifDi = (kode: KategoriPengirim['Kode']) =>
    bolehPenyediaSendiri
      ? kategori.find((jenis) => jenis.Kode === kode)?.Penyedia.find((penyedia) => penyedia.Aktif)
      : undefined;
  const emailSendiri = aktifDi('Email');
  const whatsAppSendiri = aktifDi('WhatsApp');
  const bermasalah = kategori.flatMap((jenis) =>
    jenis.Penyedia.filter((penyedia) => penyedia.Aktif && penyedia.Bermasalah).map((penyedia) => ({
      jenis,
      penyedia,
    })),
  );

  return (
    <KerangkaAplikasi>
      <Head title="Email & WhatsApp" />
      <KepalaHalaman
        judul="Email & WhatsApp"
        deskripsi="Dari mana notifikasi ke staf dikirim. Tanpa pengaturan di sini, Amanpoll yang mengirimkannya."
        className="mb-5"
      />

      <div className="space-y-4">
        {!bolehPenyediaSendiri && (
          <Alert
            variant="info"
            aksi={
              <Button asChild size="sm" variant="outline">
                <Link href={rutePengirimNotifikasi.langganan}>Lihat paket</Link>
              </Button>
            }
          >
            <AlertTitle>Paket Anda memakai email dan WhatsApp Amanpoll.</AlertTitle>
            <p>
              Mengirim dari email dan nomor WhatsApp organisasi sendiri tersedia di paket yang lebih tinggi.
            </p>
          </Alert>
        )}

        {bermasalah.map(({ jenis, penyedia }) => (
          <Alert key={`${jenis.Kode}-${penyedia.Kode}`} variant="bahaya">
            <AlertTitle>
              {penyedia.Nama} gagal mengirim {tanggalJam(penyedia.TerakhirGagalPada)}
            </AlertTitle>
            <p>
              {jenis.Kode === 'Email'
                ? 'Sementara ini email notifikasi dikirim lewat email Amanpoll.'
                : 'Notifikasi WhatsApp tidak terkirim sampai penyedianya diperbaiki.'}{' '}
              Galat terakhir: {penyedia.GalatTerakhir}
            </p>
          </Alert>
        ))}

        <Card>
          <CardHeader>
            <CardTitle>Yang dipakai sekarang</CardTitle>
          </CardHeader>
          <CardContent className="divide-y divide-border">
            <BarisPengirim ikon={<Mail aria-hidden="true" className="size-4" />} judul="Email notifikasi">
              {emailSendiri ? (
                <p>
                  Dikirim dari email organisasi lewat{' '}
                  <strong className="text-foreground">{emailSendiri.Nama}</strong>. Bila gagal, email tetap
                  dikirim lewat email Amanpoll.
                </p>
              ) : platform.EmailAktif ? (
                <>
                  <p>
                    Dikirim lewat email Amanpoll dengan nama pengirim{' '}
                    <strong className="text-foreground">&ldquo;{organisasi.Nama} via Amanpoll&rdquo;</strong>.
                  </p>
                  <p>
                    {organisasi.Email ? (
                      <>Balasan staf diarahkan ke {organisasi.Email}.</>
                    ) : (
                      <>
                        Isi email organisasi di{' '}
                        <Link
                          href={rutePengirimNotifikasi.profilOrganisasi}
                          className="text-primary underline"
                        >
                          profil organisasi
                        </Link>{' '}
                        supaya balasan staf sampai ke Anda.
                      </>
                    )}
                  </p>
                </>
              ) : (
                <p>Pengirim email Amanpoll belum aktif, jadi email notifikasi belum terkirim.</p>
              )}
            </BarisPengirim>

            <BarisPengirim
              ikon={<MessageCircle aria-hidden="true" className="size-4" />}
              judul="WhatsApp notifikasi"
            >
              {whatsAppSendiri ? (
                <p>
                  Dikirim dari nomor organisasi lewat{' '}
                  <strong className="text-foreground">{whatsAppSendiri.Nama}</strong>, tanpa kuota.
                </p>
              ) : platform.WhatsAppAktif ? (
                <KuotaBawaan kuota={kuotaWhatsApp} />
              ) : (
                <p>
                  WhatsApp Amanpoll belum aktif, jadi notifikasi WhatsApp tidak dikirim kecuali organisasi
                  memasang nomornya sendiri.
                </p>
              )}
            </BarisPengirim>
          </CardContent>
        </Card>

        {kategori.map((jenis) => (
          <Card key={jenis.Kode}>
            <CardHeader>
              <CardTitle>
                {jenis.Kode === 'Email' ? 'Email milik organisasi' : 'WhatsApp milik organisasi'}
              </CardTitle>
              <p className="text-sm text-muted-foreground">{DESKRIPSI_KATEGORI[jenis.Kode]}</p>
              {jenis.Kode === 'WhatsApp' && bolehPenyediaSendiri && !penerimaUji.WhatsApp && (
                <p className="text-xs text-muted-foreground">
                  Isi nomor telepon di{' '}
                  <Link href={rutePengirimNotifikasi.profilSaya} className="text-primary underline">
                    profil Anda
                  </Link>{' '}
                  untuk menerima WhatsApp uji.
                </p>
              )}
            </CardHeader>
            <CardContent>
              <ul className="divide-y divide-border">
                {jenis.Penyedia.map((penyedia) => (
                  <li key={penyedia.Kode} className="flex flex-wrap items-start gap-x-4 gap-y-2 py-3">
                    <div className="min-w-0 flex-1 space-y-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="font-medium text-foreground">{penyedia.Nama}</span>
                        <LencanaPenyedia
                          jenis={jenis}
                          penyedia={penyedia}
                          bolehPenyediaSendiri={bolehPenyediaSendiri}
                        />
                      </div>
                      <p className="text-sm text-muted-foreground">{penyedia.Keterangan}</p>
                    </div>
                    {bolehPenyediaSendiri ? (
                      <DialogPenyediaLayanan
                        untukOrganisasi
                        kategori={{ Kode: jenis.Kode, BolehBanyakAktif: false }}
                        penyedia={penyedia}
                        rute={{
                          simpan: rutePengirimNotifikasi.simpan(jenis.Kode, penyedia.Kode),
                          uji: rutePengirimNotifikasi.uji(jenis.Kode, penyedia.Kode),
                          kirimUji:
                            jenis.Kode === 'Email' || penerimaUji.WhatsApp
                              ? rutePengirimNotifikasi.kirimUji(jenis.Kode, penyedia.Kode)
                              : undefined,
                          hapus: rutePengirimNotifikasi.hapus(jenis.Kode, penyedia.Kode),
                        }}
                      />
                    ) : (
                      penyedia.Isian.some((isian) => isian.Tersimpan) && (
                        <TombolHapusTersisa jenis={jenis} penyedia={penyedia} />
                      )
                    )}
                  </li>
                ))}
              </ul>
            </CardContent>
          </Card>
        ))}
      </div>
    </KerangkaAplikasi>
  );
}

function BarisPengirim({ ikon, judul, children }: { ikon: ReactNode; judul: string; children: ReactNode }) {
  return (
    <div className="flex gap-3 py-3 first:pt-0 last:pb-0">
      <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-md bg-permukaan-100 text-grafit-700">
        {ikon}
      </span>
      <div className="min-w-0 flex-1 space-y-1 text-sm text-muted-foreground">
        <p className="font-medium text-foreground">{judul}</p>
        {children}
      </div>
    </div>
  );
}

const angka = (nilai: number) => nilai.toLocaleString('id-ID');

function KuotaBawaan({ kuota }: { kuota: KuotaWhatsApp }) {
  if (!kuota.TermasukPaket) {
    return <p>Dikirim lewat nomor Amanpoll, tetapi paket Anda tidak memuat kuota WhatsApp bawaan.</p>;
  }

  if (kuota.Batas === null) {
    return <p>Dikirim lewat nomor Amanpoll, tanpa batas bulanan. Bulan ini {angka(kuota.Terpakai)} pesan.</p>;
  }

  const persen = kuota.Batas === 0 ? 100 : Math.min(100, Math.round((kuota.Terpakai / kuota.Batas) * 100));

  return (
    <div className="space-y-2">
      <p>
        Dikirim lewat nomor Amanpoll. Bulan ini{' '}
        <strong className="text-foreground">
          {angka(kuota.Terpakai)} dari {angka(kuota.Batas)}
        </strong>{' '}
        pesan kuota bawaan terpakai.
      </p>
      <div
        className="h-2 w-full max-w-md overflow-hidden rounded-full bg-permukaan-100"
        role="progressbar"
        aria-label="Kuota WhatsApp bawaan"
        aria-valuemin={0}
        aria-valuemax={kuota.Batas}
        aria-valuenow={Math.min(kuota.Terpakai, kuota.Batas)}
      >
        <div
          className={
            kuota.Habis
              ? 'h-full bg-bahaya-600'
              : persen >= 80
                ? 'h-full bg-safety-500'
                : 'h-full bg-teknisi-600'
          }
          style={{ width: `${persen}%` }}
        />
      </div>
      {kuota.Habis && (
        <p className="text-bahaya-700">
          Kuota habis: notifikasi WhatsApp berhenti sampai bulan depan. Notifikasi in-app tetap berjalan.
        </p>
      )}
    </div>
  );
}

function LencanaPenyedia({
  jenis,
  penyedia,
  bolehPenyediaSendiri,
}: {
  jenis: KategoriPengirim;
  penyedia: PenyediaOrganisasi;
  bolehPenyediaSendiri: boolean;
}) {
  const tersimpan = penyedia.Isian.some((isian) => isian.Tersimpan);

  return (
    <>
      {jenis.Kode === 'WhatsApp' && (
        <Badge variant={penyedia.Resmi ? 'info' : 'perhatian'}>
          {penyedia.Resmi ? 'Resmi' : 'Tidak resmi'}
        </Badge>
      )}
      {penyedia.Aktif && !bolehPenyediaSendiri ? (
        <Badge variant="netral">Tidak dipakai paket ini</Badge>
      ) : penyedia.Aktif ? (
        <Badge variant={penyedia.Bermasalah ? 'bahaya' : 'sukses'}>
          {penyedia.Bermasalah ? 'Bermasalah' : 'Aktif'}
        </Badge>
      ) : (
        <Badge variant="netral">{tersimpan ? 'Nonaktif' : 'Belum diatur'}</Badge>
      )}
    </>
  );
}

/** Setelah paket turun, kredensial lama tetap bisa dibuang walau tidak bisa diubah. */
function TombolHapusTersisa({ jenis, penyedia }: { jenis: KategoriPengirim; penyedia: PenyediaOrganisasi }) {
  const hapus = () => {
    if (!window.confirm(`Hapus seluruh kredensial ${penyedia.Nama}?`)) {
      return;
    }
    router.delete(rutePengirimNotifikasi.hapus(jenis.Kode, penyedia.Kode), {
      preserveScroll: true,
      onSuccess: () => toast.success(`Kredensial ${penyedia.Nama} dihapus.`),
    });
  };

  return (
    <Button size="sm" variant="ghost" className="text-destructive hover:text-destructive" onClick={hapus}>
      <Trash2 aria-hidden="true" className="size-4" />
      Hapus kredensial
    </Button>
  );
}
