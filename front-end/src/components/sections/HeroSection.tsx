import { useLanguage } from '@/hooks/useLanguage';
import { useSharedData } from '@/contexts/SharedDataContext';
import { PixelButton } from '@/components/PixelButton';
import pixelDevChar from '@/assets/pixel-dev-character.png';

interface HeroSectionProps {
  onNavigate: (section: string) => void;
}

export function HeroSection({ onNavigate }: HeroSectionProps) {
  const { language } = useLanguage();
  const { heroContent, getTranslatedContent } = useSharedData();

  return (
    <section className="min-h-screen flex items-center justify-center bg-gradient-hero px-4">
      <div className="max-w-4xl mx-auto text-center">
        <div className="mb-8 animate-fade-in-up">
          <img 
            src={pixelDevChar} 
            alt="Pixel Developer Character" 
            className="w-32 h-32 md:w-48 md:h-48 mx-auto mb-8 animate-pixel-bounce image-rendering-pixelated"
          />
        </div>
        
        <div className="space-y-6 animate-fade-in-up" style={{ animationDelay: '0.2s' }}>
          <h1 className="font-display text-4xl md:text-6xl lg:text-7xl font-bold text-foreground">
            <span className="text-muted-foreground">{getTranslatedContent(heroContent.greeting, language)}</span>
            <br />
            <span className="bg-gradient-primary bg-clip-text text-transparent animate-pixel-glow">
              {heroContent.name}
            </span>
          </h1>
          
          <h2 className="font-pixel text-xl md:text-2xl lg:text-3xl text-primary font-bold">
            {getTranslatedContent(heroContent.title, language)}
          </h2>
          
          <p className="font-pixel text-lg md:text-xl text-muted-foreground max-w-2xl mx-auto">
            {getTranslatedContent(heroContent.subtitle, language)}
          </p>
          
          <div className="flex flex-col sm:flex-row gap-4 justify-center mt-8">
            <PixelButton 
              variant="hero" 
              size="lg"
              onClick={() => onNavigate('portfolio')}
              className="text-lg"
            >
              {getTranslatedContent(heroContent.ctaText, language)}
            </PixelButton>
            
            <PixelButton 
              variant="neon" 
              size="lg"
              onClick={() => onNavigate('contact')}
              className="text-lg"
            >
              Contact
            </PixelButton>
          </div>
        </div>
      </div>
    </section>
  );
}