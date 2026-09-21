import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Activity } from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { EmptyState } from '@/components/shared/EmptyState';
import { cn } from '@/lib/utils';

export interface ButirAktivitas {
  id: string;
  /** Kalimat aktivitas; dirender sebagai teks React, tidak pernah HTML mentah. */
  ringkasan: ReactNode;
  pelaku?: string | null;
  waktu?: string | null;
  ikon?: LucideIcon;
  rincian?: ReactNode;
}

function inisial(nama?: string | null): string {
  if (!nama) return '—';
  const bagian = nama.trim().split(/\s+/);

  return bagian.length === 1
    ? bagian[0].slice(0, 2).toUpperCase()
    : (bagian[0][0] + bagian[bagian.length - 1][0]).toUpperCase();
}

/**
 * Umpan aktivitas entitas (DESIGN.md 12).
 *
 * Pelaku ditampilkan sebagai inisial, bukan foto: data aktivitas dibaca dari
 * catatan audit yang tidak selalu membawa avatar, dan gambar yang gagal muat
 * pada daftar panjang lebih mengganggu daripada inisial yang selalu ada.
 */
export function ActivityFeed({
  butir,
  className,
  pesanKosong = 'Belum ada aktivitas tercatat.',
}: {
  butir: ButirAktivitas[];
  className?: string;
  pesanKosong?: string;
}) {
  if (butir.length === 0) {
    return <EmptyState judul={pesanKosong} />;
  }

  return (
    <ul className={cn('space-y-4', className)}>
      {butir.map((satu) => {
        const Ikon = satu.ikon ?? Activity;

        return (
          <li key={satu.id} className="flex gap-3">
            {satu.pelaku ? (
              <Avatar className="mt-0.5 size-7 shrink-0">
                <AvatarFallback className="bg-teknisi-700 text-[10px] font-bold text-white">
                  {inisial(satu.pelaku)}
                </AvatarFallback>
              </Avatar>
            ) : (
              <span className="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-muted">
                <Ikon aria-hidden="true" className="size-3.5 text-muted-foreground" />
              </span>
            )}

            <div className="min-w-0 flex-1 space-y-0.5">
              <div className="text-sm text-foreground">{satu.ringkasan}</div>
              {(satu.pelaku || satu.waktu) && (
                <p className="text-xs text-muted-foreground">
                  {[satu.pelaku, satu.waktu].filter(Boolean).join(' · ')}
                </p>
              )}
              {satu.rincian && <div className="pt-1 text-sm text-muted-foreground">{satu.rincian}</div>}
            </div>
          </li>
        );
      })}
    </ul>
  );
}
