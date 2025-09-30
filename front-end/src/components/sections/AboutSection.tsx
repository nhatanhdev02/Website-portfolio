import { useLanguage } from '@/hooks/useLanguage';
import { PixelCard, PixelCardContent, PixelCardHeader, PixelCardTitle } from '@/components/PixelCard';
import { Code, Database, Palette, Zap } from 'lucide-react';

const skills = [
  { icon: Code, name: 'Frontend', tech: 'React, TypeScript, Tailwind CSS' },
  { icon: Database, name: 'Backend', tech: 'Node.js, Express, PostgreSQL' },
  { icon: Palette, name: 'Design', tech: 'Figma, UI/UX, Pixel Art' },
  { icon: Zap, name: 'Tools', tech: 'Git, Docker, AWS, Vercel' },
];

export function AboutSection() {
  const { t } = useLanguage();

  return (
    <section className="min-h-screen flex items-center py-20 px-4">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-16">
          <h2 className="font-display text-4xl md:text-5xl font-bold text-primary mb-4">
            {t('about.title')}
          </h2>
          <div className="w-20 h-1 bg-gradient-primary mx-auto"></div>
        </div>

        <div className="grid lg:grid-cols-2 gap-12 items-center">
          <div className="space-y-6">
            <div className="text-lg font-pixel leading-relaxed text-foreground">
              {t('about.description')}
            </div>
            
            <div className="space-y-4">
              <h3 className="font-display text-2xl font-bold text-secondary">
                Tech Stack
              </h3>
              <div className="grid grid-cols-2 gap-4">
                {skills.map((skill, index) => {
                  const Icon = skill.icon;
                  return (
                    <div 
                      key={skill.name} 
                      className="flex items-center gap-3 p-3 bg-muted border-2 border-border hover:border-primary transition-all hover:shadow-pixel"
                      style={{ animationDelay: `${index * 0.1}s` }}
                    >
                      <Icon className="text-primary" size={24} />
                      <div>
                        <div className="font-pixel font-bold text-foreground">{skill.name}</div>
                        <div className="text-xs text-muted-foreground font-pixel">{skill.tech}</div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          <div className="flex justify-center">
            <PixelCard variant="glow" className="p-8 text-center max-w-sm">
              <PixelCardHeader>
                <PixelCardTitle className="text-3xl mb-4">5+</PixelCardTitle>
                <div className="font-pixel text-muted-foreground">
                  Năm kinh nghiệm
                </div>
              </PixelCardHeader>
              <PixelCardContent className="space-y-4">
                <div className="space-y-2">
                  <div className="font-display text-xl font-bold text-secondary">50+</div>
                  <div className="font-pixel text-sm text-muted-foreground">Dự án hoàn thành</div>
                </div>
                <div className="space-y-2">
                  <div className="font-display text-xl font-bold text-tertiary">30+</div>
                  <div className="font-pixel text-sm text-muted-foreground">Khách hàng hài lòng</div>
                </div>
              </PixelCardContent>
            </PixelCard>
          </div>
        </div>
      </div>
    </section>
  );
}