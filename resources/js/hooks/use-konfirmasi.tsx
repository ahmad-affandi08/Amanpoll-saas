import { createContext, useCallback, useContext, useMemo, useRef, useState, type ReactNode } from 'react';
import {
  DialogKonfirmasi,
  type HasilKonfirmasi,
  type OpsiKonfirmasi,
} from '@/components/shared/DialogKonfirmasi';

type MintaKonfirmasi = (opsi: OpsiKonfirmasi) => Promise<HasilKonfirmasi>;

const KonteksKonfirmasi = createContext<MintaKonfirmasi | null>(null);

/** Menyediakan satu dialog konfirmasi untuk seluruh aplikasi. */
export function PenyediaKonfirmasi({ children }: { children: ReactNode }) {
  const [opsi, setOpsi] = useState<OpsiKonfirmasi | null>(null);
  const penyelesai = useRef<((hasil: HasilKonfirmasi) => void) | null>(null);

  const minta = useCallback<MintaKonfirmasi>((opsiBaru) => {
    return new Promise<HasilKonfirmasi>((selesaikan) => {
      penyelesai.current = selesaikan;
      setOpsi(opsiBaru);
    });
  }, []);

  const selesai = useCallback((hasil: HasilKonfirmasi) => {
    setOpsi(null);
    penyelesai.current?.(hasil);
    penyelesai.current = null;
  }, []);

  const nilai = useMemo(() => minta, [minta]);

  return (
    <KonteksKonfirmasi.Provider value={nilai}>
      {children}
      <DialogKonfirmasi opsi={opsi} onSelesai={selesai} />
    </KonteksKonfirmasi.Provider>
  );
}

/** Mengganti `window.confirm`. */
export function useKonfirmasi(): MintaKonfirmasi {
  const konteks = useContext(KonteksKonfirmasi);

  if (!konteks) {
    throw new Error('useKonfirmasi harus dipakai di dalam PenyediaKonfirmasi.');
  }

  return konteks;
}
