import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

/*
 * Keadaan nonaktif memakai warna, bukan `opacity-50`: separuh transparansi
 * menurunkan teks tombol utama ke 1,6:1 dan tombol outline ke 3,2:1. Abu-abu
 * Grafit-500 di atas Garis-200 tetap 4,6:1 dan jelas tidak bisa diklik.
 *
 * Hover ghost memakai tint Teknisi-600/8 yang transparan agar tetap terlihat
 * di atas putih MAUPUN latar halaman Permukaan-100; latar hover Permukaan-100
 * yang lama tidak memberi umpan balik apa pun di atas latar halaman.
 */
const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 rounded-[7px] text-sm font-medium transition-colors cursor-pointer disabled:pointer-events-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1',
  {
    variants: {
      variant: {
        default:
          'bg-primary text-primary-foreground hover:bg-[var(--primary-hover)] disabled:bg-garis-200 disabled:text-grafit-500',
        // Tonal: Permukaan-100 (token `secondary`) sama persis dengan latar halaman, jadi tombolnya lenyap di sana.
        secondary:
          'border border-teknisi-200 bg-teknisi-100 text-teknisi-900 hover:border-teknisi-300 hover:bg-teknisi-200 disabled:border-garis-300 disabled:bg-garis-200 disabled:text-grafit-500',
        outline:
          'border border-border bg-white text-foreground hover:border-teknisi-300 hover:bg-accent disabled:bg-permukaan-100 disabled:text-grafit-500',
        ghost: 'text-foreground hover:bg-teknisi-600/8 disabled:text-grafit-500',
        destructive:
          'bg-destructive text-destructive-foreground hover:bg-bahaya-700 disabled:bg-garis-200 disabled:text-grafit-500',
      },
      // Tinggi di ponsel dinaikkan ke 44px (DESIGN.md 9.3): pekerjaan lapangan
      // dilakukan sambil berdiri, sering bersarung tangan, dan tombol setinggi
      // 32-36px terlalu mudah meleset. Dari 640px ke atas ukurannya kembali
      // padat karena di sana penunjuknya tetikus.
      size: {
        default: 'min-h-11 px-4 py-2 sm:h-9 sm:min-h-0',
        sm: 'min-h-11 px-3 sm:h-8 sm:min-h-0',
        lg: 'min-h-11 px-6 sm:h-10 sm:min-h-0',
        icon: 'size-11 sm:size-9',
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
