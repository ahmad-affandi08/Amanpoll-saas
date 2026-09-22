interface LogoProps {
  className?: string;
  alt?: string;
}

/** LogoLambang resmi Amanpoll (ikon perisai dengan peralatan teknisi). */
export function LogoLambang({ className = 'size-8', alt = 'Amanpoll' }: LogoProps) {
  return <img src="/images/branding/amanpoll-icon.png" alt={alt} className={className} loading="eager" />;
}

/** Logo Horizontal resmi Amanpoll (ikon + nama Amanpoll + subtitle). */
export function LogoMendatar({ className = 'h-8 w-auto', alt = 'Amanpoll' }: LogoProps) {
  return (
    <img
      src="/images/branding/amanpoll-logo-horizontal.png"
      alt={alt}
      className={className}
      loading="eager"
    />
  );
}

/** Logo Vertikal resmi Amanpoll (ikon di atas + nama Amanpoll di bawah). */
export function LogoTegak({ className = 'h-24 w-auto', alt = 'Amanpoll' }: LogoProps) {
  return (
    <img src="/images/branding/amanpoll-logo-vertical.png" alt={alt} className={className} loading="eager" />
  );
}
