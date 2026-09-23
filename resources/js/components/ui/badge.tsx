import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { Slot } from 'radix-ui';

/*
 * Badge Amanpoll: kotak lembut radius 5px, background tipis + teks kuat (DESIGN.md 6, 13.3, 18).
 * Teks memakai shade -700: shade -600 di atas tint-nya sendiri hanya 2,9-4,4:1 (WCAG AA 4,5:1),
 * dan tint yang transparan makin gelap di atas latar halaman.
 */
const badgeVariants = cva(
  'inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-[5px] border px-2 py-0.5 text-xs font-medium whitespace-nowrap transition-[color,box-shadow] focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1 [&>svg]:pointer-events-none [&>svg]:size-3',
  {
    variants: {
      variant: {
        // Netral/Draf
        default: 'border-garis-300 bg-permukaan-100 text-grafit-700',
        netral: 'border-garis-300 bg-permukaan-100 text-grafit-700',
        // Informasi
        info: 'border-info-600/25 bg-info-600/10 text-info-700',
        // Sedang berjalan
        proses: 'border-teknisi-600/25 bg-teknisi-600/10 text-teknisi-700',
        // Menunggu/Perhatian
        perhatian: 'border-safety-600/30 bg-safety-500/15 text-safety-700',
        // Selesai/Aktif
        sukses: 'border-sukses-600/25 bg-sukses-600/10 text-sukses-700',
        // Gagal/Overdue/Destructive
        destructive: 'border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700',
        bahaya: 'border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700',
        // Solid, untuk penekanan terbatas (mis. angka pada tab aktif)
        solid: 'border-transparent bg-primary text-primary-foreground',
        secondary: 'border-garis-300 bg-secondary text-secondary-foreground',
        outline: 'border-border bg-transparent text-foreground',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
);

function Badge({
  className,
  variant = 'default',
  asChild = false,
  ...props
}: React.ComponentProps<'span'> & VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot.Root : 'span';

  return (
    <Comp
      data-slot="badge"
      data-variant={variant}
      className={cn(badgeVariants({ variant }), className)}
      {...props}
    />
  );
}

export { Badge, badgeVariants };
