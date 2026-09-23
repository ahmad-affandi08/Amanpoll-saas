import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { CircleAlert, CircleCheck, Info, TriangleAlert, type LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

/*
 * Alert Amanpoll: banner menetap di dalam halaman, radius 9px mengikuti Card (DESIGN.md 6).
 * Teks dan ikon shade -700, sama dengan Badge: shade -600 di atas tint-nya 2,7-4,4:1.
 */
const alertVariants = cva(
  'relative flex w-full items-start gap-3 rounded-[9px] border px-4 py-3 text-sm [&>svg]:size-4 [&>svg]:shrink-0 [&>svg]:translate-y-0.5',
  {
    variants: {
      variant: {
        netral: 'border-garis-300 bg-permukaan-100 text-grafit-700 [&>svg]:text-grafit-700',
        info: 'border-info-600/25 bg-info-600/10 text-info-700 [&>svg]:text-info-700',
        perhatian: 'border-safety-600/30 bg-safety-500/15 text-safety-700 [&>svg]:text-safety-700',
        sukses: 'border-sukses-600/25 bg-sukses-600/10 text-sukses-700 [&>svg]:text-sukses-700',
        bahaya: 'border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700 [&>svg]:text-bahaya-700',
      },
    },
    defaultVariants: {
      variant: 'netral',
    },
  },
);

type VarianAlert = NonNullable<VariantProps<typeof alertVariants>['variant']>;

const IKON_BAWAAN: Record<VarianAlert, LucideIcon> = {
  netral: Info,
  info: Info,
  perhatian: TriangleAlert,
  sukses: CircleCheck,
  bahaya: CircleAlert,
};

interface AlertProps extends React.ComponentProps<'div'>, VariantProps<typeof alertVariants> {
  /** Ganti ikon bawaan varian; `false` menyembunyikan ikon. */
  ikon?: LucideIcon | false;
  /** Aksi di sisi kanan, mis. tombol "Coba lagi". */
  aksi?: React.ReactNode;
}

function Alert({ className, variant, ikon, aksi, children, ...props }: AlertProps) {
  const Ikon = ikon === false ? null : (ikon ?? IKON_BAWAAN[variant ?? 'netral']);

  return (
    <div data-slot="alert" role="alert" className={cn(alertVariants({ variant }), className)} {...props}>
      {Ikon && <Ikon aria-hidden />}
      <div className="min-w-0 flex-1 space-y-1">{children}</div>
      {aksi && <div className="shrink-0">{aksi}</div>}
    </div>
  );
}

function AlertTitle({ className, ...props }: React.ComponentProps<'div'>) {
  return <div data-slot="alert-title" className={cn('font-medium tracking-tight', className)} {...props} />;
}

function AlertDescription({ className, ...props }: React.ComponentProps<'div'>) {
  return (
    <div
      data-slot="alert-description"
      className={cn('text-sm [&_p]:leading-relaxed', className)}
      {...props}
    />
  );
}

export { Alert, AlertTitle, AlertDescription, alertVariants };
export type { VarianAlert };
