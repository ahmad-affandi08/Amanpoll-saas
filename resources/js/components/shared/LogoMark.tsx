interface LogoProps {
  className?: string;
  alt?: string;
}

/**
 * LogoMark resmi Amanpoll (ikon perisai dengan peralatan teknisi).
 */
export function LogoMark({ className = 'size-8', alt = 'Amanpoll' }: LogoProps) {
  return <img src="/images/branding/amanpoll-icon.png" alt={alt} className={className} loading="eager" />;
}

/**
 * Logo Horizontal resmi Amanpoll (ikon + nama Amanpoll + subtitle).
 * Sangat cocok untuk latar belakang gelap seperti sidebar header.
 */
export function LogoHorizontal({ className = 'h-8 w-auto', alt = 'Amanpoll' }: LogoProps) {
  return (
    <img
      src="/images/branding/amanpoll-logo-horizontal.png"
      alt={alt}
      className={className}
      loading="eager"
    />
  );
}

/**
 * Logo Vertikal resmi Amanpoll (ikon di atas + nama Amanpoll di bawah).
 * Cocok untuk halaman login atau dokumen.
 */
export function LogoVertical({ className = 'h-24 w-auto', alt = 'Amanpoll' }: LogoProps) {
  return (
    <img src="/images/branding/amanpoll-logo-vertical.png" alt={alt} className={className} loading="eager" />
  );
}
