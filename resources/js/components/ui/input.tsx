import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * Cincin fokus tipis Teknisi-600 (DESIGN.md 13.2): border + ring 1px = 2px, 5,1:1 di
 * atas putih maupun latar halaman. Ring 3px ber-opasitas 50% semula hanya 2,1:1.
 * Tinggi 32px di desktop, sama dengan tombol (DESIGN.md 13.2); minimal 40px di ponsel lewat
 * min-height, supaya tinggi khusus dari pemanggil (mis. isian autentikasi 52px) tetap menang.
 */
function Input({ className, type, ...props }: React.ComponentProps<'input'>) {
  return (
    <input
      type={type}
      data-slot="input"
      className={cn(
        'h-8 min-h-10 w-full min-w-0 rounded-sm border border-input bg-card px-2.5 py-1 text-base transition-[color,box-shadow] sm:min-h-0 outline-none selection:bg-primary selection:text-primary-foreground file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground disabled:pointer-events-none disabled:cursor-not-allowed disabled:bg-permukaan-100 disabled:text-grafit-500 md:text-sm dark:bg-input/30',
        'focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-ring',
        'aria-invalid:border-destructive aria-invalid:ring-destructive',
        className,
      )}
      {...props}
    />
  );
}

export { Input };
