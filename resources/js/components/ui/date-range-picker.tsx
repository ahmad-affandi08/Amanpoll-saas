import * as React from 'react';
import {
  format,
  parseISO,
  isValid,
  isToday,
  isSameDay,
  isSameMonth,
  startOfMonth,
  endOfMonth,
  eachDayOfInterval,
  startOfWeek,
  endOfWeek,
  addMonths,
  subMonths,
  subDays,
  startOfYear,
  endOfYear,
  isWithinInterval,
  differenceInCalendarDays,
} from 'date-fns';
import { id } from 'date-fns/locale';
import { CalendarIcon, ChevronDown, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';

export interface DateRange {
  dari?: string;
  sampai?: string;
}

export interface DateRangePickerProps {
  dari?: string | null;
  sampai?: string | null;
  value?: DateRange;
  onChange?: (range: DateRange) => void;
  placeholder?: string;
  disabled?: boolean;
  className?: string;
  align?: 'start' | 'center' | 'end';
}

export function DateRangePicker({
  dari: propDari,
  sampai: propSampai,
  value: propValue,
  onChange,
  placeholder = 'Pilih rentang tanggal',
  disabled = false,
  className,
  align = 'start',
}: DateRangePickerProps) {
  const [open, setOpen] = React.useState(false);

  // Normalisasi nilai aktif
  const aktifDari = propValue?.dari ?? propDari ?? undefined;
  const aktifSampai = propValue?.sampai ?? propSampai ?? undefined;

  const parsedDari = React.useMemo(() => {
    if (!aktifDari) return null;
    const d = parseISO(aktifDari);
    return isValid(d) ? d : null;
  }, [aktifDari]);

  const parsedSampai = React.useMemo(() => {
    if (!aktifSampai) return null;
    const d = parseISO(aktifSampai);
    return isValid(d) ? d : null;
  }, [aktifSampai]);

  // State sementara saat memilih rentang di kalender
  const [tempDari, setTempDari] = React.useState<Date | null>(parsedDari);
  const [tempSampai, setTempSampai] = React.useState<Date | null>(parsedSampai);
  const [hoverDate, setHoverDate] = React.useState<Date | null>(null);

  // Bulan navigasi tampilan kalender (bulan kiri)
  const [leftMonth, setLeftMonth] = React.useState<Date>(() => parsedDari ?? new Date());
  const rightMonth = React.useMemo(() => addMonths(leftMonth, 1), [leftMonth]);

  // Sinkronisasi state sementara saat popover dibuka
  React.useEffect(() => {
    if (open) {
      setTempDari(parsedDari);
      setTempSampai(parsedSampai);
      if (parsedDari) {
        setLeftMonth(parsedDari);
      }
    } else {
      setHoverDate(null);
    }
  }, [open, parsedDari, parsedSampai]);

  // Presets cepat
  const presets = [
    {
      label: 'Hari Ini',
      getRange: () => {
        const today = new Date();
        return { start: today, end: today };
      },
    },
    {
      label: 'Kemarin',
      getRange: () => {
        const yesterday = subDays(new Date(), 1);
        return { start: yesterday, end: yesterday };
      },
    },
    {
      label: '7 Hari Terakhir',
      getRange: () => {
        const today = new Date();
        return { start: subDays(today, 6), end: today };
      },
    },
    {
      label: '30 Hari Terakhir',
      getRange: () => {
        const today = new Date();
        return { start: subDays(today, 29), end: today };
      },
    },
    {
      label: 'Bulan Ini',
      getRange: () => {
        const today = new Date();
        return { start: startOfMonth(today), end: endOfMonth(today) };
      },
    },
    {
      label: 'Bulan Lalu',
      getRange: () => {
        const lastMonth = subMonths(new Date(), 1);
        return { start: startOfMonth(lastMonth), end: endOfMonth(lastMonth) };
      },
    },
    {
      label: 'Tahun Ini',
      getRange: () => {
        const today = new Date();
        return { start: startOfYear(today), end: endOfYear(today) };
      },
    },
  ];

  const handleApplyPreset = (getRange: () => { start: Date; end: Date }) => {
    const { start, end } = getRange();
    const formattedDari = format(start, 'yyyy-MM-dd');
    const formattedSampai = format(end, 'yyyy-MM-dd');
    onChange?.({ dari: formattedDari, sampai: formattedSampai });
    setTempDari(start);
    setTempSampai(end);
    setLeftMonth(start);
    setOpen(false);
  };

  const handleDateClick = (date: Date) => {
    if (!tempDari || (tempDari && tempSampai)) {
      setTempDari(date);
      setTempSampai(null);
    } else {
      if (date < tempDari) {
        setTempSampai(tempDari);
        setTempDari(date);
      } else {
        setTempSampai(date);
      }
    }
  };

  const handleApply = () => {
    if (!tempDari) {
      onChange?.({});
    } else if (!tempSampai) {
      const d = format(tempDari, 'yyyy-MM-dd');
      onChange?.({ dari: d, sampai: d });
    } else {
      onChange?.({
        dari: format(tempDari, 'yyyy-MM-dd'),
        sampai: format(tempSampai, 'yyyy-MM-dd'),
      });
    }
    setOpen(false);
  };

  const handleReset = (e: React.MouseEvent) => {
    e.stopPropagation();
    setTempDari(null);
    setTempSampai(null);
    onChange?.({ dari: undefined, sampai: undefined });
  };

  // Label tampilan trigger
  const labelTampilan = React.useMemo(() => {
    if (parsedDari && parsedSampai) {
      if (isSameDay(parsedDari, parsedSampai)) {
        return format(parsedDari, 'd MMM yyyy', { locale: id });
      }
      return `${format(parsedDari, 'd MMM yyyy', { locale: id })} – ${format(parsedSampai, 'd MMM yyyy', { locale: id })}`;
    }
    if (parsedDari) {
      return `Mulai: ${format(parsedDari, 'd MMM yyyy', { locale: id })}`;
    }
    return placeholder;
  }, [parsedDari, parsedSampai, placeholder]);

  // Durasi rentang yang dipilih sementara
  const infoRentang = React.useMemo(() => {
    if (tempDari && tempSampai) {
      const hari = differenceInCalendarDays(tempSampai, tempDari) + 1;
      return `${format(tempDari, 'd MMM yyyy', { locale: id })} – ${format(tempSampai, 'd MMM yyyy', { locale: id })} (${hari} hari)`;
    }
    if (tempDari && hoverDate) {
      const start = hoverDate < tempDari ? hoverDate : tempDari;
      const end = hoverDate < tempDari ? tempDari : hoverDate;
      const hari = differenceInCalendarDays(end, start) + 1;
      return `${format(start, 'd MMM yyyy', { locale: id })} – ${format(end, 'd MMM yyyy', { locale: id })} (${hari} hari)`;
    }
    if (tempDari) {
      return `Pilih tanggal akhir`;
    }
    return 'Pilih tanggal mulai';
  }, [tempDari, tempSampai, hoverDate]);

  // Helper render satu bulan
  const renderMonthCalendar = (
    monthDate: Date,
    navProps: { showPrev?: boolean; showNext?: boolean; onPrev?: () => void; onNext?: () => void } = {},
  ) => {
    const monthStart = startOfMonth(monthDate);
    const monthEnd = endOfMonth(monthStart);
    const startDate = startOfWeek(monthStart, { weekStartsOn: 1 });
    const endDate = endOfWeek(monthEnd, { weekStartsOn: 1 });
    const days = eachDayOfInterval({ start: startDate, end: endDate });
    const weekDays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

    const effectiveStart = tempDari;
    const effectiveEnd = tempSampai ?? (tempDari ? hoverDate : null);
    const rangeStart = effectiveStart && effectiveEnd ? (effectiveStart <= effectiveEnd ? effectiveStart : effectiveEnd) : effectiveStart;
    const rangeEnd = effectiveStart && effectiveEnd ? (effectiveStart <= effectiveEnd ? effectiveEnd : effectiveStart) : effectiveStart;

    return (
      <div className="w-full sm:w-[240px]">
        {/* Header Bulan */}
        <div className="flex items-center justify-between h-8 mb-2 px-1">
          {navProps.showPrev ? (
            <Button
              type="button"
              variant="ghost"
              size="icon"
              className="size-7 text-muted-foreground hover:text-foreground hover:bg-permukaan-100"
              onClick={navProps.onPrev}
            >
              <ChevronLeft className="size-4" />
            </Button>
          ) : (
            <div className="size-7" />
          )}

          <span className="text-sm font-semibold text-foreground">
            {format(monthDate, 'MMMM yyyy', { locale: id })}
          </span>

          {navProps.showNext ? (
            <Button
              type="button"
              variant="ghost"
              size="icon"
              className="size-7 text-muted-foreground hover:text-foreground hover:bg-permukaan-100"
              onClick={navProps.onNext}
            >
              <ChevronRight className="size-4" />
            </Button>
          ) : (
            <div className="size-7" />
          )}
        </div>

        {/* Header Nama Hari */}
        <div className="grid grid-cols-7 gap-1 text-center mb-1">
          {weekDays.map((hari) => (
            <div key={hari} className="text-[11px] font-medium text-muted-foreground py-0.5">
              {hari}
            </div>
          ))}
        </div>

        {/* Grid Hari */}
        <div className="grid grid-cols-7 gap-y-1">
          {days.map((day) => {
            const isCurrentMonth = isSameMonth(day, monthDate);
            const isStart = rangeStart ? isSameDay(day, rangeStart) : false;
            const isEnd = rangeEnd ? isSameDay(day, rangeEnd) : false;
            const inRange =
              rangeStart && rangeEnd && rangeStart < rangeEnd
                ? isWithinInterval(day, { start: rangeStart, end: rangeEnd })
                : false;
            const isDayToday = isToday(day);

            return (
              <div
                key={day.toISOString()}
                className={cn(
                  'h-8 flex items-center justify-center relative p-0',
                  inRange && !isStart && !isEnd && 'bg-primary/10',
                  isStart && inRange && 'bg-gradient-to-r from-transparent to-primary/10 rounded-l-md',
                  isEnd && inRange && 'bg-gradient-to-l from-transparent to-primary/10 rounded-r-md',
                )}
              >
                <button
                  type="button"
                  onClick={() => handleDateClick(day)}
                  onMouseEnter={() => {
                    if (tempDari && !tempSampai) setHoverDate(day);
                  }}
                  className={cn(
                    'size-8 flex items-center justify-center rounded-md text-xs font-normal transition-colors cursor-pointer select-none',
                    !isCurrentMonth && 'text-muted-foreground/30',
                    isDayToday && !isStart && !isEnd && 'border border-primary/50 font-medium text-primary',
                    (isStart || isEnd) &&
                      'bg-primary text-primary-foreground font-semibold shadow-xs hover:bg-primary',
                    inRange && !isStart && !isEnd && 'text-primary font-medium hover:bg-primary/20',
                    !inRange && !isStart && !isEnd && 'hover:bg-permukaan-100 text-foreground',
                  )}
                >
                  {format(day, 'd')}
                </button>
              </div>
            );
          })}
        </div>
      </div>
    );
  };

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <button
          type="button"
          disabled={disabled}
          className={cn(
            'flex h-9 w-full min-w-0 items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer text-left md:text-sm dark:bg-input/30 dark:hover:bg-input/50',
            !parsedDari && 'text-muted-foreground',
            className,
          )}
        >
          <div className="flex items-center gap-2 truncate">
            <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
            <span className={cn('truncate font-normal', parsedDari ? 'text-foreground' : 'text-muted-foreground')}>
              {labelTampilan}
            </span>
          </div>
          {parsedDari && !disabled ? (
            <span
              role="button"
              tabIndex={0}
              onClick={handleReset}
              className="rounded-full p-0.5 hover:bg-muted text-muted-foreground hover:text-foreground transition-colors cursor-pointer shrink-0"
              title="Reset rentang tanggal"
            >
              <X className="size-3.5" />
            </span>
          ) : (
            <ChevronDown className="size-4 opacity-50 shrink-0 text-muted-foreground" />
          )}
        </button>
      </PopoverTrigger>
      <PopoverContent
        className="w-[calc(100vw-2rem)] md:w-auto max-w-[660px] p-0 shadow-lg border-border bg-popover overflow-hidden"
        align={align}
      >
        {/* Presets Mobile / Layar Kecil (< 768px): Horizontal scrollable pills */}
        <div className="flex md:hidden overflow-x-auto gap-1.5 p-2 border-b border-border bg-permukaan-50 scrollbar-none">
          {presets.map((p) => (
            <Button
              key={p.label}
              type="button"
              variant="ghost"
              size="sm"
              className="h-7 px-2.5 text-xs whitespace-nowrap rounded-md shrink-0 text-muted-foreground hover:bg-permukaan-100 hover:text-foreground"
              onClick={() => handleApplyPreset(p.getRange)}
            >
              {p.label}
            </Button>
          ))}
        </div>

        <div className="flex flex-col md:flex-row">
          {/* Presets Desktop (>= 768px): Sidebar Kiri */}
          <div className="hidden md:flex border-r border-border p-2.5 flex-col gap-1 w-36 shrink-0 bg-permukaan-50">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground px-2 py-1">
              Pilihan Cepat
            </span>
            {presets.map((p) => (
              <Button
                key={p.label}
                type="button"
                variant="ghost"
                size="sm"
                className="justify-start h-7 px-2 text-xs font-normal text-muted-foreground hover:bg-permukaan-100 hover:text-foreground"
                onClick={() => handleApplyPreset(p.getRange)}
              >
                {p.label}
              </Button>
            ))}
          </div>

          {/* Area Kalender */}
          <div className="p-3 w-full flex justify-center">
            {/* Desktop (>= 768px): Dua Kalender Berdampingan */}
            <div className="hidden md:flex items-start gap-4">
              {renderMonthCalendar(leftMonth, {
                showPrev: true,
                onPrev: () => setLeftMonth((m) => subMonths(m, 1)),
              })}
              <div className="border-l border-border/60 self-stretch my-1" />
              {renderMonthCalendar(rightMonth, {
                showNext: true,
                onNext: () => setLeftMonth((m) => addMonths(m, 1)),
              })}
            </div>

            {/* Mobile (< 768px): Satu Kalender Responsif dengan Navigasi Lengkap */}
            <div className="block md:hidden w-full max-w-[280px]">
              {renderMonthCalendar(leftMonth, {
                showPrev: true,
                showNext: true,
                onPrev: () => setLeftMonth((m) => subMonths(m, 1)),
                onNext: () => setLeftMonth((m) => addMonths(m, 1)),
              })}
            </div>
          </div>
        </div>

        {/* Footer Aksi */}
        <div className="border-t border-border bg-permukaan-50 p-2.5 sm:p-3 flex flex-col sm:flex-row items-center justify-between gap-2">
          <div className="text-xs text-muted-foreground font-medium flex items-center gap-1.5 self-start sm:self-auto truncate max-w-full">
            <span className="size-2 rounded-full bg-primary shrink-0" />
            <span className="truncate">{infoRentang}</span>
          </div>
          <div className="flex items-center gap-2 w-full sm:w-auto justify-end shrink-0">
            <Button
              type="button"
              variant="outline"
              size="sm"
              className="h-8 px-3 text-xs flex-1 sm:flex-initial"
              onClick={() => {
                setTempDari(null);
                setTempSampai(null);
                onChange?.({});
                setOpen(false);
              }}
            >
              Kosongkan
            </Button>
            <Button
              type="button"
              size="sm"
              className="h-8 px-4 text-xs font-medium flex-1 sm:flex-initial"
              disabled={!tempDari}
              onClick={handleApply}
            >
              Terapkan
            </Button>
          </div>
        </div>
      </PopoverContent>
    </Popover>
  );
}
