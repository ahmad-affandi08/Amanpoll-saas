import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 rounded-[7px] text-sm font-medium transition-colors cursor-pointer disabled:pointer-events-none disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1',
  {
    variants: {
      variant: {
        default: 'bg-primary text-primary-foreground hover:bg-[var(--primary-hover)]',
        secondary: 'bg-secondary text-secondary-foreground border border-border hover:bg-garis-200',
        outline: 'border border-border bg-white text-foreground hover:bg-permukaan-100',
        ghost: 'text-foreground hover:bg-permukaan-100',
        destructive: 'bg-destructive text-destructive-foreground hover:bg-[#a8342f]',
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
