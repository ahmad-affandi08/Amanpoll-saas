import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { MessageSquarePlus, Timer } from 'lucide-react';
import type { FungsiAntrikan } from '@/hooks/use-sinkronisasi-offline';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { PenugasanOffline } from '@/features/Sinkronisasi/types';

/** Draft pekerjaan dan draft catatan (20.05). */
export function DialogKerjakan({
  pekerjaan,
  onTutup,
  onAntrikan,
}: {
  pekerjaan: PenugasanOffline | null;
  onTutup: () => void;
  onAntrikan: FungsiAntrikan;
}) {
  const [catatan, setCatatan] = useState('');
  const [ringkasan, setRingkasan] = useState('');
  const [mulaiPada, setMulaiPada] = useState('');
  const [selesaiPada, setSelesaiPada] = useState('');

  useEffect(() => {
    setCatatan('');
    setRingkasan('');
    setMulaiPada('');
    setSelesaiPada('');
  }, [pekerjaan?.Id]);

  if (!pekerjaan) return null;

  const tutupSetelahAntri = async (pesan: string, jalankan: () => Promise<void>) => {
    await jalankan();
    toast.success(pesan);
    onTutup();
  };

  return (
    <Dialog open onOpenChange={(terbuka) => !terbuka && onTutup()}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{pekerjaan.Judul}</DialogTitle>
          <DialogDescription>
            Perubahan disimpan di perangkat lebih dulu, lalu dikirim saat koneksi kembali.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-5">
          {pekerjaan.PerluResponsPenugasan && (
            <section className="space-y-2 rounded-[9px] border border-border bg-permukaan-100 p-3">
              <Label>Penugasan belum direspons</Label>
              <p className="text-xs text-muted-foreground">
                Terima dulu penugasan ini sebelum mencatat pekerjaan.
              </p>
              <div className="flex flex-wrap gap-2">
                {(['Terima', 'Tolak'] as const).map((respons) => (
                  <Button
                    key={respons}
                    size="sm"
                    variant={respons === 'Terima' ? 'default' : 'outline'}
                    onClick={() =>
                      void tutupSetelahAntri(`Respons "${respons}" masuk antrean.`, () =>
                        onAntrikan({
                          Operasi: 'PerintahKerja.ResponsPenugasan',
                          EntitasId: pekerjaan.Id,
                          VersiKlien: pekerjaan.Versi,
                          MuatanData: { Respons: respons, Catatan: catatan || null },
                          Label: `${pekerjaan.Nomor}: penugasan ${respons.toLowerCase()}`,
                        }),
                      )
                    }
                  >
                    {respons} penugasan
                  </Button>
                ))}
              </div>
            </section>
          )}

          <section className="space-y-2">
            <Label>Ubah status</Label>
            {pekerjaan.StatusTujuan.length === 0 && (
              <p className="text-xs text-muted-foreground">
                Tidak ada perubahan status yang dapat Anda lakukan pada pekerjaan ini.
              </p>
            )}
            <div className="flex flex-wrap gap-2">
              {pekerjaan.StatusTujuan.map((tujuan) => (
                <Button
                  key={tujuan}
                  size="sm"
                  variant="outline"
                  onClick={() =>
                    void tutupSetelahAntri(`Perubahan status ke ${tujuan} masuk antrean.`, () =>
                      onAntrikan({
                        Operasi: 'PerintahKerja.UbahStatus',
                        EntitasId: pekerjaan.Id,
                        VersiKlien: pekerjaan.Versi,
                        MuatanData: {
                          Status: tujuan,
                          Catatan: catatan || null,
                          Ringkasan: ringkasan || null,
                        },
                        Label: `${pekerjaan.Nomor}: status → ${tujuan}`,
                      }),
                    )
                  }
                >
                  {tujuan}
                </Button>
              ))}
            </div>
            <Textarea
              value={ringkasan}
              onChange={(e) => setRingkasan(e.target.value)}
              placeholder="Ringkasan penyelesaian (wajib sebelum verifikasi)"
              rows={2}
            />
          </section>

          <section className="space-y-2">
            <Label htmlFor="catatan-lapangan">Catatan lapangan</Label>
            <Textarea
              id="catatan-lapangan"
              value={catatan}
              onChange={(e) => setCatatan(e.target.value)}
              placeholder="Temuan, kendala, atau tindakan yang dilakukan"
              rows={3}
            />
            <Button
              size="sm"
              variant="outline"
              disabled={catatan.trim() === ''}
              onClick={() =>
                void tutupSetelahAntri('Catatan masuk antrean.', () =>
                  onAntrikan({
                    Operasi: 'PerintahKerja.TambahCatatan',
                    EntitasId: pekerjaan.Id,
                    VersiKlien: null,
                    MuatanData: { Isi: catatan.trim() },
                    Label: `${pekerjaan.Nomor}: catatan lapangan`,
                  }),
                )
              }
            >
              <MessageSquarePlus className="size-4" />
              Antrikan catatan
            </Button>
          </section>

          <section className="space-y-2">
            <Label>Sesi waktu kerja</Label>
            <div className="grid gap-2 sm:grid-cols-2">
              <Input
                type="datetime-local"
                value={mulaiPada}
                onChange={(e) => setMulaiPada(e.target.value)}
                aria-label="Mulai pada"
              />
              <Input
                type="datetime-local"
                value={selesaiPada}
                onChange={(e) => setSelesaiPada(e.target.value)}
                aria-label="Selesai pada"
              />
            </div>
            <Button
              size="sm"
              variant="outline"
              disabled={mulaiPada === '' || selesaiPada === ''}
              onClick={() =>
                void tutupSetelahAntri('Sesi waktu kerja masuk antrean.', () =>
                  onAntrikan({
                    Operasi: 'PerintahKerja.CatatWaktuKerja',
                    EntitasId: pekerjaan.Id,
                    VersiKlien: null,
                    MuatanData: {
                      MulaiPada: new Date(mulaiPada).toISOString(),
                      SelesaiPada: new Date(selesaiPada).toISOString(),
                      Catatan: catatan || null,
                    },
                    Label: `${pekerjaan.Nomor}: sesi waktu kerja`,
                  }),
                )
              }
            >
              <Timer className="size-4" />
              Antrikan sesi
            </Button>
          </section>
        </div>
      </DialogContent>
    </Dialog>
  );
}
