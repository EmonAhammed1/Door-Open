
import cardJournalImg from "../../assets/drumi/card-journal.png";
import cardBlogImg from "../../assets/drumi/card-blog.png";

interface DrumiVisionAndCardsProps {
  onOpenJournal: () => void;
  onOpenBlog: () => void;
}

export function DrumiVisionAndCards({ onOpenJournal, onOpenBlog }: DrumiVisionAndCardsProps) {
  return (
    <section className="w-full bg-[#f6f0ea] py-14 sm:py-20 px-4 sm:px-8 border-t border-[#e2d7cc]">
      <div className="max-w-[960px] mx-auto flex flex-col items-center">
        {/* Section Header: OUR VISION */}
        <div className="flex flex-col items-center text-center max-w-[620px] mb-12 sm:mb-16">
          <span className="text-[#8f7b6b] font-['Cinzel',serif] text-[0.68rem] tracking-[0.32em] uppercase font-semibold mb-3">
            O U R &nbsp; V I S I O N
          </span>

          <h3 className="font-['Cinzel',serif] text-[1.8rem] sm:text-[2.3rem] text-[#342921] font-normal leading-tight tracking-wide mb-4">
            Remember. Understand. Integrate.
          </h3>

          <span className="text-[#a07b78] text-[0.95rem] mb-4">✦</span>

          <p className="font-['Cormorant_Garamond',serif] text-[#635548] text-[1.15rem] sm:text-[1.28rem] leading-[1.65] text-center">
            We believe your dreams are more than stories.
            <br className="hidden sm:inline" />
            They are messages from within, guiding you back to what truly matters.
          </p>
        </div>

        {/* The Two Luxury Feature Cards */}
        <div className="w-full grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
          {/* Card 1: THE DREAM JOURNAL */}
          <div
            onClick={onOpenJournal}
            className="group relative w-full aspect-[304/134] sm:min-h-[220px] rounded-[3px] overflow-hidden p-6 sm:p-8 flex flex-col items-center justify-center text-center cursor-pointer shadow-[0_8px_24px_rgba(120,80,80,0.1)] transition-all duration-500 hover:shadow-[0_12px_32px_rgba(120,80,80,0.2)] hover:-translate-y-1"
          >
            {/* Background Texture from Mockup */}
            <div
              className="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105"
              style={{ backgroundImage: `url(${cardJournalImg})` }}
            />
            {/* Subtle Gradient Scrim for Contrast */}
            <div className="absolute inset-0 bg-[#8c6764]/20 group-hover:bg-[#8c6764]/10 transition-colors duration-300" />

            {/* Content */}
            <div className="relative z-10 flex flex-col items-center">
              {/* Droplet Logo Icon */}
              <div className="w-6 h-6 mb-2 text-[#f6efe9] opacity-90">
                <svg viewBox="0 0 40 48" fill="none" stroke="currentColor" strokeWidth="2.2" className="w-full h-full">
                  <path d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z" />
                  <circle cx="20" cy="32" r="6" />
                </svg>
              </div>

              <h4 className="font-['Cinzel',serif] text-[0.88rem] sm:text-[0.98rem] tracking-[0.24em] uppercase text-[#fdfaf7] font-medium mb-1.5 pl-[0.24em]">
                T H E &nbsp; D R E A M &nbsp; J O U R N A L
              </h4>

              <p className="font-['Cormorant_Garamond',serif] italic text-[#f3e7df] text-[0.95rem] sm:text-[1.05rem] mb-4">
                Your space to remember, reflect and receive.
              </p>

              <button
                type="button"
                className="px-4 py-1.5 rounded-[2px] border border-[#f5e7dd]/60 text-[#fff] text-[0.62rem] sm:text-[0.68rem] tracking-[0.22em] uppercase font-['Cinzel',serif] backdrop-blur-[2px] transition-all duration-300 group-hover:bg-[#fff] group-hover:text-[#7a5855] group-hover:border-white"
              >
                DISCOVER THE JOURNAL
              </button>
            </div>
          </div>

          {/* Card 2: THE BLOG */}
          <div
            onClick={onOpenBlog}
            className="group relative w-full aspect-[304/134] sm:min-h-[220px] rounded-[3px] overflow-hidden p-6 sm:p-8 flex flex-col items-center justify-center text-center cursor-pointer shadow-[0_8px_24px_rgba(90,80,70,0.1)] transition-all duration-500 hover:shadow-[0_12px_32px_rgba(90,80,70,0.2)] hover:-translate-y-1"
          >
            {/* Background Texture from Mockup */}
            <div
              className="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105"
              style={{ backgroundImage: `url(${cardBlogImg})` }}
            />
            {/* Subtle Gradient Scrim for Contrast */}
            <div className="absolute inset-0 bg-[#dfd3c5]/30 group-hover:bg-transparent transition-colors duration-300" />

            {/* Content */}
            <div className="relative z-10 flex flex-col items-center">
              {/* Feather Quill Icon */}
              <div className="w-6 h-6 mb-2 text-[#5a483a] opacity-85">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="w-full h-full">
                  <path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z" />
                  <line x1="16" y1="8" x2="2" y2="22" />
                  <line x1="17.5" y1="15" x2="9" y2="15" />
                </svg>
              </div>

              <h4 className="font-['Cinzel',serif] text-[0.88rem] sm:text-[0.98rem] tracking-[0.24em] uppercase text-[#3e3125] font-medium mb-1.5 pl-[0.24em]">
                T H E &nbsp; B L O G
              </h4>

              <p className="font-['Cormorant_Garamond',serif] italic text-[#5f4e40] text-[0.95rem] sm:text-[1.05rem] mb-4">
                Insights, inspiration and guidance for your journey.
              </p>

              <button
                type="button"
                className="px-4 py-1.5 rounded-[2px] border border-[#7a6452]/50 text-[#3e3125] text-[0.62rem] sm:text-[0.68rem] tracking-[0.22em] uppercase font-['Cinzel',serif] backdrop-blur-[2px] transition-all duration-300 group-hover:bg-[#3e3125] group-hover:text-[#f8f2eb] group-hover:border-[#3e3125]"
              >
                EXPLORE THE BLOG
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
