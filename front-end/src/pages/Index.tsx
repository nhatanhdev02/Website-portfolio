import { useState, useEffect } from 'react';
import { Navigation } from '@/components/Navigation';
import { HeroSection } from '@/components/sections/HeroSection';
import { AboutSection } from '@/components/sections/AboutSection';
import { ServicesSection } from '@/components/sections/ServicesSection';
import { PortfolioSection } from '@/components/sections/PortfolioSection';
import { BlogSection } from '@/components/sections/BlogSection';
import { ContactSection } from '@/components/sections/ContactSection';
import { useLanguage } from '@/hooks/useLanguage';

const Index = () => {
  const [activeSection, setActiveSection] = useState('home');
  const { t } = useLanguage();

  // Smooth scroll to section
  const scrollToSection = (sectionId: string) => {
    const element = document.getElementById(sectionId);
    if (element) {
      element.scrollIntoView({ 
        behavior: 'smooth',
        block: 'start'
      });
    }
    setActiveSection(sectionId);
  };

  // Handle scroll to update active section
  useEffect(() => {
    const handleScroll = () => {
      const sections = ['home', 'about', 'services', 'portfolio', 'blog', 'contact'];
      const scrollPosition = window.scrollY + 100;

      for (const section of sections) {
        const element = document.getElementById(section);
        if (element) {
          const { offsetTop, offsetHeight } = element;
          if (scrollPosition >= offsetTop && scrollPosition < offsetTop + offsetHeight) {
            setActiveSection(section);
            break;
          }
        }
      }
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  return (
    <div className="min-h-screen bg-background text-foreground font-pixel">
      {/* Navigation */}
      <Navigation activeSection={activeSection} onSectionChange={scrollToSection} />
      
      {/* Main Content */}
      <main className="md:mr-20 lg:mr-64">
        {/* Hero Section */}
        <div id="home">
          <HeroSection onNavigate={scrollToSection} />
        </div>

        {/* About Section */}
        <div id="about">
          <AboutSection />
        </div>

        {/* Services Section */}
        <div id="services">
          <ServicesSection />
        </div>

        {/* Portfolio Section */}
        <div id="portfolio">
          <PortfolioSection />
        </div>

        {/* Blog Section */}
        <div id="blog">
          <BlogSection />
        </div>

        {/* Contact Section */}
        <div id="contact">
          <ContactSection />
        </div>
      </main>

      {/* Footer */}
      <footer className="md:mr-20 lg:mr-64 mb-20 md:mb-0 bg-muted/30 border-t-2 border-border py-8 px-4">
        <div className="max-w-6xl mx-auto text-center">
          <div className="mb-4">
            <h3 className="font-display text-xl font-bold text-primary">Nhật Anh Dev</h3>
            <p className="font-pixel text-sm text-muted-foreground mt-2">
              Freelance Fullstack Developer
            </p>
          </div>
          <div className="text-xs font-pixel text-muted-foreground">
            {t('footer.copyright')}
          </div>
        </div>
      </footer>
    </div>
  );
};

export default Index;
