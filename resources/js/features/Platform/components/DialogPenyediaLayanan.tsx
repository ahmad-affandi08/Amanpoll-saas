import { type FormEvent, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { isAxiosError } from 'axios';
import { Settings2, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { http } from '@/lib/http';
import type { KategoriPenyediaLayanan, PenyediaLayanan } from '@/features/Platform/types';

export interface RutePenyediaLayanan {
  simpan: string;
  uji: string;
  /** Email uji ke alamat pengguna, atau WhatsApp uji ke nomornya. */
  kirimUji?: string;
  /** Hanya organisasi: membuang kredensial seutuhnya. */
  hapus?: string;
}

interface Props {
  kategori: Pick<KategoriPenyediaLayanan, 'Kode' | 'BolehBanyakAktif'>;
  penyedia: PenyediaLayanan;
  rute: RutePenyediaLayanan;
  /** Pengaturan milik organisasi (notifikasi staf), bukan konsol platform. */
  untukOrganisasi?: boolean;
}

function nilaiAwal(penyedia: PenyediaLayanan) {
  return {
    Aktif: penyedia.Aktif,
    Utama: penyedia.Utama,
    ModeUji: penyedia.ModeUji,
    // Isian rahasia selalu dimulai kosong: kosong berarti "pertahankan yang tersimpan".
    Kredensial: Object.fromEntries(
      penyedia.Isian.map((isian) => [isian.Kunci, isian.Rahasia ? '' : (isian.Nilai ?? '')]),
    ),
  };
}

/**
 * Atur satu penyedia: aktif/utama, mode uji, dan kredensialnya (PRD 8.23).
 * Rahasia yang tersimpan hanya ditandai "tersimpan", tidak pernah ditampilkan.
 * Dipakai konsol platform dan halaman Email & WhatsApp organisasi.
 */
export function DialogPenyediaLayanan({ kategori, penyedia, rute, untukOrganisasi = false }: Props) {
  const [buka, setBuka] = useState(false);
  const [menguji, setMenguji] = useState(false);
  const form = useForm(nilaiAwal(penyedia));

  const ubahBuka = (terbuka: boolean) => {
    if (terbuka) {
      form.setData(nilaiAwal(penyedia));
      form.clearErrors();
    }
    setBuka(terbuka);
  };

  const ubahIsian = (kunci: string, nilai: string) => {
    form.setData({ ...form.data, Kredensial: { ...form.data.Kredensial, [kunci]: nilai } });
  };

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.put(rute.simpan, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(`${penyedia.Nama} disimpan.`);
        setBuka(false);
      },
    });
  };

  const jalankanUji = async (url: string, pesanGagal: string) => {
    setMenguji(true);
    try {
      const { data } = await http.post<{ Berhasil: boolean; Pesan: string }>(url);
      toast.success(data.Pesan);
    } catch (galat) {
      const pesan = isAxiosError<{ Pesan?: string }>(galat) ? galat.response?.data?.Pesan : undefined;
      toast.error(pesan ?? pesanGagal);
    } finally {
      setMenguji(false);
    }
  };

  const uji = () => jalankanUji(rute.uji, 'Uji koneksi gagal. Coba lagi.');

  const kirimUji = () =>
    rute.kirimUji &&
    jalankanUji(
      rute.kirimUji,
      kategori.Kode === 'Email'
        ? 'Email uji belum terkirim. Coba lagi.'
        : 'WhatsApp uji belum terkirim. Coba lagi.',
    );

  const adaTersimpan = penyedia.Isian.some((isian) => isian.Tersimpan);

  const hapus = () => {
    if (!rute.hapus || !window.confirm(`Hapus seluruh kredensial ${penyedia.Nama}?`)) {
      return;
    }
    router.delete(rute.hapus, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(`Kredensial ${penyedia.Nama} dihapus.`);
        setBuka(false);
      },
    });
  };

  const galatKredensial = (form.errors as Record<string, string | undefined>).Kredensial;

  return (
    <Dialog open={buka} onOpenChange={ubahBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          <Settings2 aria-hidden="true" className="size-4" />
          Atur
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] max-w-xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{penyedia.Nama}</DialogTitle>
          <DialogDescription>{penyedia.Keterangan}</DialogDescription>
        </DialogHeader>

        {kategori.Kode === 'WhatsApp' && !penyedia.Resmi && (
          <Alert variant="perhatian">
            Penyedia tidak resmi memakai WhatsApp biasa lewat pindai QR. Nomornya bisa diblokir WhatsApp kapan
            saja. Pakai nomor khusus, bukan nomor utama perusahaan.
          </Alert>
        )}

        <form onSubmit={kirim} className="space-y-5">
          <div className="space-y-3">
            <label className="flex items-start gap-3 text-sm">
              <Switch
                checked={form.data.Aktif}
                onCheckedChange={(nilai) => form.setData({ ...form.data, Aktif: nilai })}
              />
              <span>
                <span className="block font-medium text-foreground">Aktifkan</span>
                <span className="block text-xs text-muted-foreground">
                  {untukOrganisasi
                    ? 'Notifikasi organisasi dikirim lewat penyedia ini; mengaktifkannya menonaktifkan penyedia lain di kategori yang sama.'
                    : kategori.BolehBanyakAktif
                      ? 'Penyedia aktif muncul sebagai pilihan cara bayar bagi pelanggan.'
                      : 'Hanya satu penyedia yang dipakai; mengaktifkan ini menonaktifkan yang lain.'}
                </span>
              </span>
            </label>

            {kategori.BolehBanyakAktif && form.data.Aktif && (
              <label className="flex items-start gap-3 text-sm">
                <Switch
                  checked={form.data.Utama}
                  onCheckedChange={(nilai) => form.setData({ ...form.data, Utama: nilai })}
                />
                <span>
                  <span className="block font-medium text-foreground">Jadikan utama</span>
                  <span className="block text-xs text-muted-foreground">
                    Pilihan pertama yang ditawarkan kepada pelanggan.
                  </span>
                </span>
              </label>
            )}

            {penyedia.MendukungModeUji && (
              <label className="flex items-start gap-3 text-sm">
                <Switch
                  checked={form.data.ModeUji}
                  onCheckedChange={(nilai) => form.setData({ ...form.data, ModeUji: nilai })}
                />
                <span>
                  <span className="block font-medium text-foreground">Mode uji (sandbox)</span>
                  <span className="block text-xs text-muted-foreground">
                    Transaksi dan pesan tidak sungguhan. Matikan setelah kredensial produksi diisi.
                  </span>
                </span>
              </label>
            )}
          </div>

          <fieldset className="space-y-4">
            <legend className="text-sm font-medium text-foreground">Kredensial</legend>
            {penyedia.Isian.map((isian) => {
              const id = `isian-${penyedia.Kode}-${isian.Kunci}`;
              const nilai = form.data.Kredensial[isian.Kunci] ?? '';

              return (
                <div key={isian.Kunci} className="space-y-1.5">
                  <Label htmlFor={id} wajib={isian.Wajib && !isian.Tersimpan}>
                    {isian.Label}
                  </Label>
                  {isian.Pilihan.length > 0 ? (
                    <Select value={nilai} onValueChange={(pilihan) => ubahIsian(isian.Kunci, pilihan)}>
                      <SelectTrigger id={id} className="w-full">
                        <SelectValue placeholder="Pilih" />
                      </SelectTrigger>
                      <SelectContent>
                        {isian.Pilihan.map((pilihan) => (
                          <SelectItem key={pilihan} value={pilihan}>
                            {pilihan}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  ) : (
                    <Input
                      id={id}
                      type={isian.Rahasia ? 'password' : 'text'}
                      autoComplete={isian.Rahasia ? 'new-password' : 'off'}
                      spellCheck={false}
                      value={nilai}
                      placeholder={
                        isian.Rahasia && isian.Tersimpan
                          ? `Tersimpan${isian.Akhiran ? ` (…${isian.Akhiran})` : ''}. Kosongkan bila tidak diganti.`
                          : (isian.Bawaan ?? undefined)
                      }
                      onChange={(e) => ubahIsian(isian.Kunci, e.target.value)}
                    />
                  )}
                  {isian.Petunjuk && <p className="text-xs text-muted-foreground">{isian.Petunjuk}</p>}
                </div>
              );
            })}
            {galatKredensial && <p className="text-sm text-destructive">{galatKredensial}</p>}
          </fieldset>

          <DialogFooter className="gap-2 sm:justify-between">
            <div className="flex flex-wrap gap-2">
              {penyedia.DapatDiuji && (
                <Button type="button" variant="ghost" onClick={uji} disabled={menguji || form.processing}>
                  {menguji ? 'Menguji…' : 'Uji kredensial tersimpan'}
                </Button>
              )}
              {rute.kirimUji && (
                <Button
                  type="button"
                  variant="ghost"
                  onClick={kirimUji}
                  disabled={menguji || form.processing}
                >
                  {kategori.Kode === 'Email' ? 'Kirim email uji ke saya' : 'Kirim WhatsApp uji ke saya'}
                </Button>
              )}
              {rute.hapus && adaTersimpan && (
                <Button
                  type="button"
                  variant="ghost"
                  className="text-destructive hover:text-destructive"
                  onClick={hapus}
                  disabled={menguji || form.processing}
                >
                  <Trash2 aria-hidden="true" className="size-4" />
                  Hapus kredensial
                </Button>
              )}
            </div>
            <div className="flex gap-2">
              <Button type="button" variant="outline" onClick={() => ubahBuka(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={form.processing}>
                Simpan
              </Button>
            </div>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
