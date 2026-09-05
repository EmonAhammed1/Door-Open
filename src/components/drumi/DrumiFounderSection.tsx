
import founderPhotoImg from "../../assets/drumi/founder-photo.png";

interface DrumiFounderSectionProps {
  onOpenStory: () => void;
}

export function DrumiFounderSection({ onOpenStory }: DrumiFounderSectionProps) {
  return (
    <section id="founder-section" className="w-full bg-[#f4ede6] border-t border-[#dfd4c8] py-12 sm:py-16 px-4 sm:px-8">
      <div className="max-w-[960px] mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 items-center">
        {/* Left Editorial Copy */}
        <div className="flex flex-col items-start pr-0 md:pr-4">
          <span className="text-[#8f7b6b] font-['Cinzel',serif] text-[0.68rem] tracking-[0.32em] uppercase font-semibold mb-3">
            O U R &nbsp; F O U N D E R
          </span>

          <h2 className="font-['Cinzel',serif] text-[1.85rem] sm:text-[2.25rem] text-[#342921] font-normal leading-[1.25] mb-5 tracking-wide">
            This is a journey
            <br />
            I once had to take.
          </h2>

          <p className="font-['Cormorant_Garamond',serif] text-[#635548] text-[1.12rem] sm:text-[1.25rem] leading-[1.65] mb-7 max-w-[440px]">
            Through my dreams, I found the wisdom I didn&apos;t know I was looking for.
            DRUMI was created to help you do the same — gently, honestly, and in your
            own time.
          </p>

          <button
            type="button"
            onClick={onOpenStory}
            className="group relative inline-flex items-center text-[#504033] font-['Cinzel',serif] text-[0.72rem] tracking-[0.25em] uppercase font-medium pb-1"
          >
            <span>R E A D &nbsp; M Y &nbsp; S T O R Y</span>
            <span className="absolute bottom-0 left-0 w-full h-[1px] bg-[#9a7e6b] transition-all duration-300 group-hover:h-[1.5px] group-hover:bg-[#342921]"></span>
          </button>
        </div>

        {/* Right Photo (exact from mockup: woman meditating looking out window onto the lake) */}
        <div className="relative w-full flex justify-center md:justify-end">
          <div className="relative w-full max-w-[480px] aspect-[381/257] overflow-hidden rounded-[2px] shadow-[0_10px_30px_rgba(70,50,40,0.12)] border border-[#decbc0]">
            <img
              src={founderPhotoImg}
              alt="DRUMI Founder gazing out window over mountain lake"
              className="w-full h-full object-cover transition-transform duration-700 hover:scale-105"
            />
            {/* Soft inner vignette */}
            <div className="absolute inset-0 ring-1 ring-inset ring-black/5 pointer-events-none" />
          </div>
        </div>
      </div>
    </section>
  );
}
