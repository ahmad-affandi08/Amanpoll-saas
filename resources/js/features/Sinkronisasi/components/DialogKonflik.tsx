import { useState } from 'react';
import { toast } from 'sonner';
import { CheckCircle2, Package } from 'lucide-react';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import type { MutasiOffline } from '@/features/Sinkronisasi/types';

/** Dialog konflik (20.06). */
export function DialogKonflik({
  mutasi,
  onTutup,
  onSelesaikan,
}: {
  mutasi: MutasiOffline | null;
  onTutup: () => void;
  onSelesaikan: ReturnType<typeof useSinkronisasiOffline>['selesaikanKonflik'];
}) {
  const [memproses, setMemproses] = useState(false);

  if (!mutasi) return null;

  const putuskan = async (keputusan: 'PakaiServer' | 'TerapkanUlang') => {
    setMemproses(true);
    try {
      await onSelesaikan(mutasi.KunciOperasi, keputusan);
      toast.success(
        keputusan === 'PakaiServer'
          ? 'Perubahan lokal dibuang, versi server dipertahankan.'
          : 'Perubahan diterapkan ulang di atas versi server.',
      );
      onTutup();
    } catch {
      toast.error('Konflik belum dapat diselesaikan. Coba lagi saat koneksi stabil.');
    } finally {
      setMemproses(false);
    }
  };

  return (
    <Dialog open onOpenChange={(terbuka) => !terbuka && onTutup()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Konflik perubahan</DialogTitle>
          <DialogDescription>{mutasi.Konflik?.Pesan}</DialogDescription>
        </DialogHeader>

        <div className="grid gap-3 sm:grid-cols-2">
          <div className="rounded-md border border-border bg-permukaan-100 p-3">
            <p className="mb-1 flex items-center gap-1.5 text-xs font-semibold text-foreground">
              <Package className="size-3.5" /> Di perangkat ini
            </p>
            <pre className="whitespace-pre-wrap break-words font-mono text-xs text-muted-foreground">
              {JSON.stringify(mutasi.Konflik?.NilaiKlien ?? mutasi.MuatanData, null, 2)}
            </pre>
            {mutasi.Konflik?.VersiKlien != null && (
              <p className="mt-1 text-xs text-muted-foreground">Versi {mutasi.Konflik.VersiKlien}</p>
            )}
          </div>
          <div className="rounded-md border border-border bg-card p-3">
            <p className="mb-1 flex items-center gap-1.5 text-xs font-semibold text-foreground">
              <CheckCircle2 className="size-3.5" /> Di server
            </p>
            <pre className="whitespace-pre-wrap break-words font-mono text-xs text-muted-foreground">
              {JSON.stringify(mutasi.Konflik?.NilaiServer ?? {}, null, 2)}
            </pre>
            {mutasi.Konflik?.VersiServer != null && (
              <p className="mt-1 text-xs text-muted-foreground">Versi {mutasi.Konflik.VersiServer}</p>
            )}
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" disabled={memproses} onClick={() => void putuskan('PakaiServer')}>
            Pakai versi server
          </Button>
          <Button disabled={memproses} onClick={() => void putuskan('TerapkanUlang')}>
            Terapkan ulang perubahan saya
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
