interface DrumiFooterBarProps {
  onItemClick: (label: string, quote: string) => void;
}

const ITEMS = [
  {
    id: "slow-down",
    label: "SLOW DOWN",
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" className="w-4 h-4">
        <path d="M12 2C8 6 4 11 4 16C4 19.314 7.582 22 12 22C16.418 22 20 19.314 20 16C20 11 16 6 12 2Z" />
        <path d="M12 8V18" />
        <path d="M12 12C14 10 16 11 17 13" />
        <path d="M12 14C10 12 8 13 7 15" />
      </svg>
    ),
    quote: "In stillness, the answers you have been chasing gently find their way home to you.",
  },
  {
    id: "listen-within",
    label: "LISTEN WITHIN",
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" className="w-4 h-4">
        <circle cx="12" cy="12" r="9" />
        <circle cx="12" cy="12" r="5" strokeDasharray="2 2" />
        <circle cx="12" cy="12" r="1.5" fill="currentColor" />
      </svg>
    ),
    quote: "Your intuition is the quiet whisper of your truest self. Give it the silence it deserves.",
  },
  {
    id: "drumi-center",
    label: "DRUMI",
    isCenter: true,
    icon: (
      <svg viewBox="0 0 40 48" fill="none" stroke="currentColor" strokeWidth="2" className="w-5 h-5">
        <path d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z" />
        <circle cx="20" cy="32" r="6" />
      </svg>
    ),
    quote: "A sanctuary for your dreams. A journey back to yourself.",
  },
  {
    id: "trust-the-message",
    label: "TRUST THE MESSAGE",
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" className="w-4 h-4">
        <path d="M12 2L14.4 9.6L22 12L14.4 14.4L12 22L9.6 14.4L2 12L9.6 9.6L12 2Z" />
      </svg>
    ),
    quote: "Every dream carries a seed of wisdom. Trust what surfaces in the quiet of night.",
  },
  {
    id: "return-to-you",
    label: "RETURN TO YOU",
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" className="w-4 h-4">
        <circle cx="12" cy="12" r="9" />
        <circle cx="12" cy="12" r="3" />
      </svg>
    ),
    quote: "You were never lost. You are simply remembering who you have always been.",
  },
];

export function DrumiFooterBar({ onItemClick }: DrumiFooterBarProps) {
  return (
    <footer className="w-full bg-[#966f6c] border-t border-[#ab8582] py-4 px-3 sm:px-6">
      <div className="max-w-[960px] mx-auto flex flex-wrap items-center justify-between gap-y-3 gap-x-2 text-[#fbf7f4]">
        {ITEMS.map((item) => {
          if (item.isCenter) {
            return (
              <button
                key={item.id}
                type="button"
                onClick={() => {
                  onItemClick(item.label, item.quote);
                }}
                className="group flex flex-col items-center justify-center px-3 py-1 text-center transition-transform hover:scale-105"
              >
                <div className="text-[#f9ece6] mb-0.5 opacity-95 group-hover:text-white">{item.icon}</div>
                <span className="font-['Cinzel',serif] text-[0.72rem] sm:text-[0.8rem] tracking-[0.32em] uppercase font-semibold text-[#fbf7f4] pl-[0.32em]">
                  D R U M I
                </span>
              </button>
            );
          }

          return (
            <button
              key={item.id}
              type="button"
              onClick={() => {
                onItemClick(item.label, item.quote);
              }}
              className="flex items-center gap-1.5 sm:gap-2 px-2 py-1 text-[#eedcd7] hover:text-[#fff] transition-colors group"
            >
              <span className="opacity-80 group-hover:opacity-100 transition-opacity">{item.icon}</span>
              <span className="font-['Cinzel',serif] text-[0.58rem] sm:text-[0.66rem] tracking-[0.2em] uppercase whitespace-nowrap">
                {item.label}
              </span>
            </button>
          );
        })}
      </div>
    </footer>
  );
}
