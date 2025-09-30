import { useLanguage } from '@/hooks/useLanguage';
import { PixelCard, PixelCardContent, PixelCardHeader, PixelCardTitle, PixelCardDescription } from '@/components/PixelCard';
import { PixelButton } from '@/components/PixelButton';
import { Calendar, ArrowRight } from 'lucide-react';
import apiTutorialImg from '@/assets/blog-api-tutorial.png';
import reactOptimizationImg from '@/assets/blog-react-optimization.png';

const blogPosts = [
  {
    id: 'api-tutorial',
    image: apiTutorialImg,
    titleKey: 'blog.api.title',
    descKey: 'blog.api.desc',
    date: '2024-03-15',
    readTime: '10 phút',
    category: 'Backend',
  },
  {
    id: 'react-optimization',
    image: reactOptimizationImg,
    titleKey: 'blog.react.title',
    descKey: 'blog.react.desc',
    date: '2024-03-10',
    readTime: '8 phút',
    category: 'Frontend',
  },
];

export function BlogSection() {
  const { t } = useLanguage();

  return (
    <section className="min-h-screen flex items-center py-20 px-4 bg-muted/30">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-16">
          <h2 className="font-display text-4xl md:text-5xl font-bold text-primary mb-4">
            {t('blog.title')}
          </h2>
          <div className="w-20 h-1 bg-gradient-secondary mx-auto"></div>
        </div>

        <div className="grid md:grid-cols-2 gap-8">
          {blogPosts.map((post, index) => (
            <PixelCard 
              key={post.id} 
              variant="hover"
              className="overflow-hidden animate-fade-in-up"
              style={{ animationDelay: `${index * 0.2}s` }}
            >
              <div className="relative group">
                <img 
                  src={post.image} 
                  alt={t(post.titleKey)}
                  className="w-full h-48 object-cover image-rendering-pixelated"
                />
                <div className="absolute top-4 left-4">
                  <span className="px-2 py-1 text-xs font-pixel bg-secondary text-secondary-foreground border border-secondary">
                    {post.category}
                  </span>
                </div>
              </div>
              
              <PixelCardHeader>
                <div className="flex items-center gap-2 text-sm text-muted-foreground font-pixel mb-2">
                  <Calendar size={14} />
                  <span>{new Date(post.date).toLocaleDateString('vi-VN')}</span>
                  <span>•</span>
                  <span>{post.readTime}</span>
                </div>
                <PixelCardTitle className="text-xl hover:text-primary transition-colors">
                  {t(post.titleKey)}
                </PixelCardTitle>
                <PixelCardDescription className="font-pixel">
                  {t(post.descKey)}
                </PixelCardDescription>
              </PixelCardHeader>
              
              <PixelCardContent>
                <PixelButton variant="ghost" size="sm" className="group/btn">
                  Đọc thêm
                  <ArrowRight size={16} className="ml-2 group-hover/btn:translate-x-1 transition-transform" />
                </PixelButton>
              </PixelCardContent>
            </PixelCard>
          ))}
        </div>

        <div className="text-center mt-12">
          <PixelButton variant="outline" size="lg">
            Xem tất cả bài viết
          </PixelButton>
        </div>
      </div>
    </section>
  );
}