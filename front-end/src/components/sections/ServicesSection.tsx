import { useLanguage } from '@/hooks/useLanguage';
import { PixelCard, PixelCardContent, PixelCardHeader, PixelCardTitle, PixelCardDescription } from '@/components/PixelCard';
import { Globe, Server, Palette, Zap } from 'lucide-react';

const services = [
  {
    icon: Globe,
    titleKey: 'services.web.title',
    descKey: 'services.web.desc',
    color: 'text-primary',
    bgColor: 'bg-primary/10',
  },
  {
    icon: Server,
    titleKey: 'services.api.title',
    descKey: 'services.api.desc',
    color: 'text-secondary',
    bgColor: 'bg-secondary/10',
  },
  {
    icon: Palette,
    titleKey: 'services.ui.title',
    descKey: 'services.ui.desc',
    color: 'text-tertiary',
    bgColor: 'bg-tertiary/10',
  },
  {
    icon: Zap,
    titleKey: 'services.optimization.title',
    descKey: 'services.optimization.desc',
    color: 'text-warning',
    bgColor: 'bg-warning/10',
  },
];

export function ServicesSection() {
  const { t } = useLanguage();

  return (
    <section className="min-h-screen flex items-center py-20 px-4 bg-muted/30">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-16">
          <h2 className="font-display text-4xl md:text-5xl font-bold text-primary mb-4">
            {t('services.title')}
          </h2>
          <div className="w-20 h-1 bg-gradient-secondary mx-auto"></div>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          {services.map((service, index) => {
            const Icon = service.icon;
            return (
              <PixelCard 
                key={service.titleKey} 
                variant="hover"
                className="h-full animate-fade-in-up"
                style={{ animationDelay: `${index * 0.1}s` }}
              >
                <PixelCardHeader className="text-center">
                  <div className={`w-16 h-16 mx-auto mb-4 flex items-center justify-center border-2 ${service.bgColor} ${service.color} border-current`}>
                    <Icon size={32} />
                  </div>
                  <PixelCardTitle className="text-xl">
                    {t(service.titleKey)}
                  </PixelCardTitle>
                </PixelCardHeader>
                <PixelCardContent>
                  <PixelCardDescription className="text-center font-pixel">
                    {t(service.descKey)}
                  </PixelCardDescription>
                </PixelCardContent>
              </PixelCard>
            );
          })}
        </div>
      </div>
    </section>
  );
}