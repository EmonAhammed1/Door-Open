
import founderPhotoImg from "../../assets/drumi/founder-photo.png";

export type DrumiModalType = "founder" | "journal" | "blog" | "wisdom" | null;

interface DrumiModalsProps {
  modalType: DrumiModalType;
  wisdomData?: { title: string; quote: string } | null;
  onClose: () => void;
}

export function DrumiModals({ modalType, wisdomData, onClose }: DrumiModalsProps) {
  if (!modalType) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#2b211a]/70 backdrop-blur-sm animate-fadeIn"
      onClick={onClose}
    >
      <div
        className="relative w-full max-w-[620px] max-h-[90vh] overflow-y-auto bg-[#fdfaf7] rounded-[3px] shadow-[0_25px_60px_rgba(0,0,0,0.3)] border border-[#decbc0] p-6 sm:p-10"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Close Button */}
        <button
          type="button"
          onClick={onClose}
          className="absolute top-5 right-5 w-8 h-8 flex items-center justify-center rounded-full text-[#7d6957] hover:bg-[#ebd9cb] transition-colors"
          aria-label="Close modal"
        >
          ✕
        </button>

        {/* Modal 1: Founder Story */}
        {modalType === "founder" && (
          <div className="flex flex-col items-start">
            <span className="text-[#8f7b6b] font-['Cinzel',serif] text-[0.68rem] tracking-[0.32em] uppercase font-semibold mb-2">
              O U R &nbsp; F O U N D E R
            </span>
            <h3 className="font-['Cinzel',serif] text-[1.65rem] sm:text-[2rem] text-[#342921] font-normal leading-snug mb-4">
              A Journey I Once Had to Take
            </h3>
            
            <div className="w-full aspect-[16/9] mb-5 rounded-[2px] overflow-hidden border border-[#decbc0]">
              <img
                src={founderPhotoImg}
                alt="Founder meditating"
                className="w-full h-full object-cover"
              />
            </div>

            <div className="font-['Cormorant_Garamond',serif] text-[#55473c] text-[1.15rem] leading-[1.7] space-y-4">
              <p>
                Years ago, in the midst of relentless noise and unending expectations,
                I felt completely disconnected from my own soul. It wasn&apos;t until I began
                paying close attention to my dreams each morning that a quiet compass began to point me home.
              </p>
              <p>
                Our dreams are not random illusions. They are the sacred architecture of
                the unconscious — unfiltered, profound, and deeply healing.
              </p>
              <p>
                I created <strong>DRUMI</strong> as a physical and spiritual sanctuary. A space
                crafted with the purity of natural stone, warm linen, and serene morning light,
                where you are given permission to slow down, listen within, and remember who you truly are.
              </p>
            </div>

            <div className="mt-8 pt-4 border-t border-[#e2d5c8] w-full flex justify-between items-center text-[#7d6957] font-['Cinzel',serif] text-[0.72rem] tracking-[0.2em] uppercase">
              <span>Warmly, Elena Vasquez</span>
              <span>Founder, DRUMI</span>
            </div>
          </div>
        )}

        {/* Modal 2: The Dream Journal */}
        {modalType === "journal" && (
          <div className="flex flex-col items-start">
            <span className="text-[#966f6c] font-['Cinzel',serif] text-[0.68rem] tracking-[0.32em] uppercase font-semibold mb-2">
              S A N C T U A R Y &nbsp; T O O L S
            </span>
            <h3 className="font-['Cinzel',serif] text-[1.65rem] sm:text-[2rem] text-[#342921] font-normal leading-snug mb-3">
              The Dream Journal
            </h3>
            <p className="font-['Cormorant_Garamond',serif] italic text-[#635548] text-[1.15rem] mb-6">
              &ldquo;Your space to remember, reflect and receive.&rdquo;
            </p>

            <div className="w-full space-y-4 font-['Cormorant_Garamond',serif] text-[#55473c] text-[1.1rem]">
              <div className="p-4 rounded-[2px] bg-[#f7efe9] border border-[#e8d7cb]">
                <h5 className="font-['Cinzel',serif] text-[0.78rem] tracking-[0.18em] uppercase text-[#7d5653] mb-1 font-medium">
                  Prompt I · The Morning Whisper
                </h5>
                <p>What feeling lingered in your chest the exact moment you woke today?</p>
              </div>

              <div className="p-4 rounded-[2px] bg-[#f7efe9] border border-[#e8d7cb]">
                <h5 className="font-['Cinzel',serif] text-[0.78rem] tracking-[0.18em] uppercase text-[#7d5653] mb-1 font-medium">
                  Prompt II · The Hidden Symbol
                </h5>
                <p>What landscape, creature, or door appeared in your dreamscape?</p>
              </div>

              <div className="mt-4">
                <label className="block font-['Cinzel',serif] text-[0.7rem] tracking-[0.2em] uppercase text-[#7d6957] mb-2">
                  Capture Your Dream Reflection
                </label>
                <textarea
                  rows={4}
                  placeholder="Record your morning memory here before the world awakens..."
                  className="w-full p-3 rounded-[2px] border border-[#decbc0] bg-white text-[#3e3125] font-['Cormorant_Garamond',serif] text-[1.1rem] focus:outline-none focus:ring-1 focus:ring-[#966f6c]"
                />
              </div>

              <button
                type="button"
                onClick={() => alert("Your reflection has been preserved in your session memory.")}
                className="w-full py-2.5 rounded-[2px] bg-[#966f6c] hover:bg-[#835d5a] text-white font-['Cinzel',serif] text-[0.72rem] tracking-[0.22em] uppercase transition-colors"
              >
                Preserve This Reflection
              </button>
            </div>
          </div>
        )}

        {/* Modal 3: The Blog */}
        {modalType === "blog" && (
          <div className="flex flex-col items-start">
            <span className="text-[#8f7b6b] font-['Cinzel',serif] text-[0.68rem] tracking-[0.32em] uppercase font-semibold mb-2">
              D R U M I &nbsp; E D I T I O N S
            </span>
            <h3 className="font-['Cinzel',serif] text-[1.65rem] sm:text-[2rem] text-[#342921] font-normal leading-snug mb-4">
              Insights & Guidance
            </h3>

            <div className="w-full space-y-4">
              <article className="p-4 rounded-[2px] bg-[#f7f3ee] border border-[#e4d7ca] hover:border-[#966f6c] transition-colors cursor-pointer">
                <span className="font-['Cinzel',serif] text-[0.62rem] tracking-[0.2em] text-[#966f6c] uppercase">Essays on Sleep</span>
                <h5 className="font-['Cinzel',serif] text-[0.95rem] text-[#342921] font-medium mt-1 mb-1">
                  The Architecture of the Liminal Hour
                </h5>
                <p className="font-['Cormorant_Garamond',serif] text-[#635548] text-[1.05rem]">
                  Why the moments between waking and sleeping carry the clearest intuitive answers.
                </p>
              </article>

              <article className="p-4 rounded-[2px] bg-[#f7f3ee] border border-[#e4d7ca] hover:border-[#966f6c] transition-colors cursor-pointer">
                <span className="font-['Cinzel',serif] text-[0.62rem] tracking-[0.2em] text-[#966f6c] uppercase">Ritual Practices</span>
                <h5 className="font-['Cinzel',serif] text-[0.95rem] text-[#342921] font-medium mt-1 mb-1">
                  Tending the Sacred Evening Threshold
                </h5>
                <p className="font-['Cormorant_Garamond',serif] text-[#635548] text-[1.05rem]">
                  Three simple grounding habits to prepare your mind for honest dreaming.
                </p>
              </article>

              <article className="p-4 rounded-[2px] bg-[#f7f3ee] border border-[#e4d7ca] hover:border-[#966f6c] transition-colors cursor-pointer">
                <span className="font-['Cinzel',serif] text-[0.62rem] tracking-[0.2em] text-[#966f6c] uppercase">Integration</span>
                <h5 className="font-['Cinzel',serif] text-[0.95rem] text-[#342921] font-medium mt-1 mb-1">
                  Bringing the Night&apos;s Wisdom into Daylight
                </h5>
                <p className="font-['Cormorant_Garamond',serif] text-[#635548] text-[1.05rem]">
                  How to interpret symbols not with a dictionary, but with your body&apos;s truth.
                </p>
              </article>
            </div>
          </div>
        )}

        {/* Modal 4: Wisdom Touchpoint */}
        {modalType === "wisdom" && wisdomData && (
          <div className="flex flex-col items-center text-center py-6">
            <span className="text-[#966f6c] text-xl mb-3">✦</span>
            <h4 className="font-['Cinzel',serif] text-[1.25rem] sm:text-[1.5rem] tracking-[0.28em] text-[#342921] uppercase font-medium mb-4 pl-[0.28em]">
              {wisdomData.title}
            </h4>
            <p className="font-['Cormorant_Garamond',serif] italic text-[#55473c] text-[1.35rem] sm:text-[1.55rem] leading-[1.5] max-w-[480px]">
              &ldquo;{wisdomData.quote}&rdquo;
            </p>
            <button
              type="button"
              onClick={onClose}
              className="mt-8 px-6 py-2 rounded-full border border-[#966f6c] text-[#966f6c] hover:bg-[#966f6c] hover:text-white font-['Cinzel',serif] text-[0.68rem] tracking-[0.22em] uppercase transition-colors"
            >
              Continue Journey
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
