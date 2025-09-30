import { useLanguage } from '@/hooks/useLanguage';
import { PixelCard, PixelCardContent, PixelCardHeader, PixelCardTitle, PixelCardDescription } from '@/components/PixelCard';
import { PixelButton } from '@/components/PixelButton';
import { ExternalLink, Github } from 'lucide-react';
import ecommerceProject from '@/assets/project-ecommerce.png';
import apiProject from '@/assets/project-api.png';

const projects = [
  {
    id: 'ecommerce',
    image: ecommerceProject,
    titleKey: 'portfolio.ecommerce.title',
    descKey: 'portfolio.ecommerce.desc',
    technologies: ['React', 'Node.js', 'PostgreSQL', 'Stripe'],
    demoUrl: '#',
    githubUrl: '#',
  },
  {
    id: 'api',
    image: apiProject,
    titleKey: 'portfolio.api.title',
    descKey: 'portfolio.api.desc',
    technologies: ['Express', 'MongoDB', 'JWT', 'Swagger'],
    demoUrl: '#',
    githubUrl: '#',
  },
];

export function PortfolioSection() {
  const { t } = useLanguage();

  return (
    <section className="min-h-screen flex items-center py-20 px-4">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-16">
          <h2 className="font-display text-4xl md:text-5xl font-bold text-primary mb-4">
            {t('portfolio.title')}
          </h2>
          <div className="w-20 h-1 bg-gradient-primary mx-auto"></div>
        </div>

        <div className="grid md:grid-cols-2 gap-8">
          {projects.map((project, index) => (
            <PixelCard 
              key={project.id} 
              variant="hover"
              className="overflow-hidden animate-fade-in-up"
              style={{ animationDelay: `${index * 0.2}s` }}
            >
              <div className="relative group">
                <img 
                  src={project.image} 
                  alt={t(project.titleKey)}
                  className="w-full h-48 object-cover image-rendering-pixelated"
                />
                <div className="absolute inset-0 bg-primary/80 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4">
                  <PixelButton variant="secondary" size="sm" asChild>
                    <a href={project.demoUrl} target="_blank" rel="noopener noreferrer">
                      <ExternalLink size={16} />
                      Demo
                    </a>
                  </PixelButton>
                  <PixelButton variant="outline" size="sm" asChild>
                    <a href={project.githubUrl} target="_blank" rel="noopener noreferrer">
                      <Github size={16} />
                      Code
                    </a>
                  </PixelButton>
                </div>
              </div>
              
              <PixelCardHeader>
                <PixelCardTitle className="text-xl">
                  {t(project.titleKey)}
                </PixelCardTitle>
                <PixelCardDescription className="font-pixel">
                  {t(project.descKey)}
                </PixelCardDescription>
              </PixelCardHeader>
              
              <PixelCardContent>
                <div className="flex flex-wrap gap-2">
                  {project.technologies.map((tech) => (
                    <span 
                      key={tech}
                      className="px-2 py-1 text-xs font-pixel bg-primary/10 text-primary border border-primary"
                    >
                      {tech}
                    </span>
                  ))}
                </div>
              </PixelCardContent>
            </PixelCard>
          ))}
        </div>

        <div className="text-center mt-12">
          <PixelButton variant="outline" size="lg">
            Xem thêm dự án
          </PixelButton>
        </div>
      </div>
    </section>
  );
}