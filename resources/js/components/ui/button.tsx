import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

/*
 * Arah N "Presisi" (DESIGN.md 13.1): tombol datar setinggi 32px di desktop, radius 6px,
 * teks 13px, tanpa bayangan. Hanya tombol utama yang berwarna; outline, sekunder, dan
 * ghost netral abu-abu supaya satu layar tidak dipenuhi biru.
 *
 * Keadaan nonaktif memakai warna, bukan `opacity-50`: separuh transparansi
 * menurunkan teks tombol utama ke 1,6:1 dan tombol outline ke 3,2:1. Abu-abu
 * Grafit-500 di atas Garis-200 tetap 4,6:1 dan jelas tidak bisa diklik.
 *
 * Hover ghost dan sekunder memakai tint Grafit-950 yang transparan agar tetap
 * terlihat di atas putih maupun permukaan abu-abu (sidebar, kepala tabel).
 */
const buttonVariants = cva(
  'inline-flex items-center justify-center gap-1.5 rounded-sm text-[13px] font-medium transition-colors [&_svg]:shrink-0 cursor-pointer disabled:pointer-events-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1',
  {
    variants: {
      variant: {
        default:
          'bg-primary text-primary-foreground hover:bg-[var(--primary-hover)] disabled:bg-garis-200 disabled:text-grafit-500',
        // Tonal: Permukaan-100 (token `secondary`) sama persis dengan latar halaman, jadi tombolnya lenyap di sana.
        secondary:
          'bg-grafit-950/5 text-foreground hover:bg-grafit-950/8 disabled:bg-garis-200 disabled:text-grafit-500',
        outline:
          'border border-input bg-card text-foreground hover:bg-permukaan-50 hover:border-grafit-500/40 disabled:bg-permukaan-100 disabled:text-grafit-500',
        ghost: 'text-grafit-700 hover:bg-grafit-950/5 hover:text-foreground disabled:text-grafit-500',
        destructive:
          'bg-destructive text-destructive-foreground hover:bg-bahaya-700 disabled:bg-garis-200 disabled:text-grafit-500',
      },
      // Tinggi di ponsel dinaikkan ke 44px (DESIGN.md 9.3): pekerjaan lapangan
      // dilakukan sambil berdiri, sering bersarung tangan, dan tombol setinggi
      // 32-36px terlalu mudah meleset. Dari 640px ke atas ukurannya kembali
      // padat (32px, sama dengan isian) karena di sana penunjuknya tetikus.
      size: {
        default: 'min-h-11 px-3 sm:h-8 sm:min-h-0',
        sm: 'min-h-11 px-2.5 sm:h-8 sm:min-h-0',
        lg: 'min-h-11 px-4 text-sm sm:h-9 sm:min-h-0',
        icon: 'size-11 sm:size-8',
      },
    },
    defaultVariants: { variant: 'default', size: 'default' },
  },
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
  asChild?: boolean;
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot : 'button';
    return <Comp ref={ref} className={cn(buttonVariants({ variant, size }), className)} {...props} />;
  },
);
Button.displayName = 'Button';
