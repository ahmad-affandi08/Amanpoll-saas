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

  // Bulan navigasi tampilan kalender
  const [leftMonth, setLeftMonth] = React.useState<Date>(() => parsedDari ?? new Date());
  const [rightMonth, setRightMonth] = React.useState<Date>(() => addMonths(parsedDari ?? new Date(), 1));

  // Sinkronisasi state sementara saat popover dibuka
  React.useEffect(() => {
    if (open) {
      setTempDari(parsedDari);
      setTempSampai(parsedSampai);
      if (parsedDari) {
        setLeftMonth(parsedDari);
        const next = addMonths(parsedDari, 1);
        if (parsedSampai && !isSameMonth(parsedDari, parsedSampai)) {
          setRightMonth(parsedSampai);
        } else {
          setRightMonth(next);
        }
      }
    } else {
      setHoverDate(null);
    }
  }, [open, parsedDari, parsedSampai]);

  // Presets cepat
  const presets = React.useMemo(
    () => [
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
    ],
    [],
  );

  const handleApplyPreset = (getRange: () => { start: Date; end: Date }) => {
    const { start, end } = getRange();
    const formattedDari = format(start, 'yyyy-MM-dd');
    const formattedSampai = format(end, 'yyyy-MM-dd');
    onChange?.({ dari: formattedDari, sampai: formattedSampai });
    setTempDari(start);
    setTempSampai(end);
    setLeftMonth(start);
    setRightMonth(addMonths(start, 1));
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

  // Helper render satu bulan kalender
  const renderMonthCalendar = (
    monthDate: Date,
    onPrev: () => void,
    onNext: () => void,
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
    const hasValidRange = rangeStart && rangeEnd && !isSameDay(rangeStart, rangeEnd) && rangeStart < rangeEnd;

    return (
      <div className="w-[266px] shrink-0">
        {/* Header Bulan & Navigasi */}
        <div className="flex items-center justify-between h-9 mb-2 px-1">
          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="size-7 rounded-md text-permukaan-600 hover:text-permukaan-900 hover:bg-permukaan-100 cursor-pointer"
            onClick={onPrev}
            title="Bulan sebelumnya"
          >
            <ChevronLeft className="size-4" />
          </Button>

          <span className="text-sm font-bold text-permukaan-900 tracking-tight capitalize select-none">
            {format(monthDate, 'MMMM yyyy', { locale: id })}
          </span>

          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="size-7 rounded-md text-permukaan-600 hover:text-permukaan-900 hover:bg-permukaan-100 cursor-pointer"
            onClick={onNext}
            title="Bulan berikutnya"
          >
            <ChevronRight className="size-4" />
          </Button>
        </div>

        {/* Header Nama Hari */}
        <div className="grid grid-cols-7 text-center mb-1.5 select-none">
          {weekDays.map((hari) => (
            <div key={hari} className="h-6 flex items-center justify-center text-[11px] font-semibold text-permukaan-400 uppercase tracking-wider">
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
            const inRange = hasValidRange
              ? isWithinInterval(day, { start: rangeStart, end: rangeEnd })
              : false;
            const isDayToday = isToday(day);

            return (
              <div
                key={day.toISOString()}
                className="h-8.5 w-full flex items-center justify-center relative p-0"
              >
                {/* Pita latar belakang rentang (Continuous Ribbon) */}
                {hasValidRange && inRange && (
                  <div
                    className={cn(
                      'absolute inset-y-0.5 bg-teknisi-100/70',
                      isStart && 'left-1/2 right-0 rounded-l-none',
                      isEnd && 'left-0 right-1/2 rounded-r-none',
                      !isStart && !isEnd && 'left-0 right-0',
                    )}
                  />
                )}

                {/* Tombol tanggal */}
                <button
                  type="button"
                  onClick={() => handleDateClick(day)}
                  onMouseEnter={() => {
                    if (tempDari && !tempSampai) setHoverDate(day);
                  }}
                  className={cn(
                    'relative z-10 size-8 flex items-center justify-center text-xs transition-all cursor-pointer select-none rounded-full',
                    // Warna teks bulan aktif vs luar bulan
                    isCurrentMonth ? 'text-permukaan-800' : 'text-permukaan-300 hover:text-permukaan-500',
                    // Hari ini
                    isDayToday && !isStart && !isEnd && !inRange && 'border border-teknisi-600 font-bold text-teknisi-700',
                    // Titik awal & akhir terpilih
                    (isStart || isEnd) &&
                      'bg-teknisi-700 text-white font-bold shadow-sm hover:bg-teknisi-800 hover:text-white',
                    // Tanggal di antara rentang
                    inRange && !isStart && !isEnd && 'font-semibold text-teknisi-900 hover:bg-teknisi-200/80',
                    // Hover normal
                    !inRange && !isStart && !isEnd && 'hover:bg-permukaan-100',
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
            'flex h-9 w-full min-w-0 items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer text-left dark:bg-input/30 dark:hover:bg-input/50',
            !parsedDari && 'text-muted-foreground',
            className,
          )}
        >
          <div className="flex items-center gap-2 truncate">
            <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
            <span className={cn('truncate font-medium', parsedDari ? 'text-foreground' : 'text-muted-foreground')}>
              {labelTampilan}
            </span>
          </div>
          {parsedDari && !disabled ? (
            <span
              role="button"
              tabIndex={0}
              onClick={handleReset}
              className="rounded-full p-1 hover:bg-permukaan-100 text-muted-foreground hover:text-foreground transition-colors cursor-pointer shrink-0"
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
        className="w-auto max-w-[calc(100vw-1rem)] md:max-w-[730px] p-0 shadow-2xl border border-permukaan-200 bg-card rounded-2xl overflow-hidden"
        align={align}
      >
        {/* Presets Mobile: Scrollable pills */}
        <div className="flex md:hidden overflow-x-auto gap-1.5 p-2.5 border-b border-permukaan-200 bg-permukaan-50 scrollbar-none">
          {presets.map((p) => {
            const r = p.getRange();
            const isActive =
              tempDari && tempSampai && isSameDay(tempDari, r.start) && isSameDay(tempSampai, r.end);
            return (
              <Button
                key={p.label}
                type="button"
                variant={isActive ? 'default' : 'ghost'}
                size="sm"
                className={cn(
                  'h-7 px-2.5 text-xs whitespace-nowrap rounded-lg shrink-0 cursor-pointer',
                  isActive
                    ? 'bg-teknisi-700 text-white hover:bg-teknisi-800'
                    : 'text-permukaan-700 hover:bg-permukaan-100',
                )}
                onClick={() => handleApplyPreset(p.getRange)}
              >
                {p.label}
              </Button>
            );
          })}
        </div>

        <div className="flex flex-col md:flex-row">
          {/* Presets Desktop: Sidebar Kiri */}
          <div className="hidden md:flex border-r border-permukaan-200 p-3 flex-col gap-1 w-40 shrink-0 bg-permukaan-50/70">
            <span className="text-[11px] font-bold uppercase tracking-wider text-permukaan-400 px-2 py-1 mb-1 select-none">
              Pilihan Cepat
            </span>
            {presets.map((p) => {
              const r = p.getRange();
              const isActive =
                tempDari && tempSampai && isSameDay(tempDari, r.start) && isSameDay(tempSampai, r.end);
              return (
                <Button
                  key={p.label}
                  type="button"
                  variant={isActive ? 'default' : 'ghost'}
                  size="sm"
                  className={cn(
                    'justify-start h-8 px-2.5 text-xs font-medium rounded-lg cursor-pointer transition-all',
                    isActive
                      ? 'bg-teknisi-700 text-white hover:bg-teknisi-800 shadow-xs'
                      : 'text-permukaan-700 hover:bg-permukaan-100 hover:text-permukaan-900',
                  )}
                  onClick={() => handleApplyPreset(p.getRange)}
                >
                  {p.label}
                </Button>
              );
            })}
          </div>

          {/* Area Kalender */}
          <div className="p-4 w-full flex justify-center">
            {/* Desktop: Dua Kalender Berdampingan dengan ruang lega */}
            <div className="hidden md:flex items-start gap-6">
              {renderMonthCalendar(
                leftMonth,
                () => {
                  setLeftMonth((m) => subMonths(m, 1));
                  setRightMonth((m) => subMonths(m, 1));
                },
                () => {
                  setLeftMonth((m) => addMonths(m, 1));
                  setRightMonth((m) => addMonths(m, 1));
                },
              )}

              <div className="w-px bg-permukaan-200 self-stretch my-2" />

              {renderMonthCalendar(
                rightMonth,
                () => {
                  setLeftMonth((m) => subMonths(m, 1));
                  setRightMonth((m) => subMonths(m, 1));
                },
                () => {
                  setLeftMonth((m) => addMonths(m, 1));
                  setRightMonth((m) => addMonths(m, 1));
                },
              )}
            </div>

            {/* Mobile: Satu Kalender Responsif */}
            <div className="block md:hidden w-full max-w-[280px]">
              {renderMonthCalendar(
                leftMonth,
                () => setLeftMonth((m) => subMonths(m, 1)),
                () => setLeftMonth((m) => addMonths(m, 1)),
              )}
            </div>
          </div>
        </div>

        {/* Footer Aksi */}
        <div className="border-t border-permukaan-200 bg-permukaan-50 px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div className="text-xs text-permukaan-600 font-medium flex items-center gap-2 self-start sm:self-auto truncate max-w-full">
            {tempDari && tempSampai ? (
              <div className="flex items-center gap-2 flex-wrap">
                <CalendarIcon className="size-4 text-teknisi-700 shrink-0" />
                <span className="font-semibold text-permukaan-900">
                  {format(tempDari, 'd MMM yyyy', { locale: id })} – {format(tempSampai, 'd MMM yyyy', { locale: id })}
                </span>
                <span className="text-[11px] font-bold bg-teknisi-100 text-teknisi-800 px-2 py-0.5 rounded-full border border-teknisi-200">
                  {differenceInCalendarDays(tempSampai, tempDari) + 1} hari
                </span>
              </div>
            ) : tempDari ? (
              <div className="flex items-center gap-2">
                <span className="size-2 rounded-full bg-safety-500 animate-pulse shrink-0" />
                <span className="text-permukaan-700">
                  Mulai: <strong>{format(tempDari, 'd MMM yyyy', { locale: id })}</strong> — Silakan pilih tanggal akhir
                </span>
              </div>
            ) : (
              <div className="flex items-center gap-1.5 text-permukaan-400">
                <span className="size-2 rounded-full bg-permukaan-300 shrink-0" />
                <span>Pilih tanggal awal dan akhir pada kalender</span>
              </div>
            )}
          </div>

          <div className="flex items-center gap-2 w-full sm:w-auto justify-end shrink-0">
            <Button
              type="button"
              variant="outline"
              size="sm"
              className="h-8 px-3.5 text-xs rounded-lg cursor-pointer border-permukaan-200 hover:bg-permukaan-100 text-permukaan-700"
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
              className={cn(
                'h-8 px-4 text-xs font-semibold rounded-lg cursor-pointer shadow-xs transition-all',
                tempDari
                  ? 'bg-teknisi-700 hover:bg-teknisi-800 text-white'
                  : 'bg-permukaan-200 text-permukaan-400 cursor-not-allowed pointer-events-none',
              )}
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
