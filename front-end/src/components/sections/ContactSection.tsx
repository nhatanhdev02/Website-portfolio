import { useState } from 'react';
import { useLanguage } from '@/hooks/useLanguage';
import { PixelCard, PixelCardContent, PixelCardHeader, PixelCardTitle } from '@/components/PixelCard';
import { PixelButton } from '@/components/PixelButton';
import { Mail, Phone, Github, Linkedin, Send } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

const contactInfo = [
  {
    icon: Mail,
    label: 'Email',
    value: 'nhatanhdev@gmail.com',
    href: 'mailto:nhatanhdev@gmail.com',
  },
  {
    icon: Phone,
    label: 'Phone',
    value: '+84 123 456 789',
    href: 'tel:+84123456789',
  },
  {
    icon: Github,
    label: 'GitHub',
    value: 'github.com/nhatanhdev',
    href: 'https://github.com/nhatanhdev',
  },
  {
    icon: Linkedin,
    label: 'LinkedIn',
    value: 'linkedin.com/in/nhatanhdev',
    href: 'https://linkedin.com/in/nhatanhdev',
  },
];

export function ContactSection() {
  const { t } = useLanguage();
  const { toast } = useToast();
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    message: '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    // Simulate form submission
    await new Promise(resolve => setTimeout(resolve, 1000));

    toast({
      title: "Tin nhắn đã được gửi!",
      description: "Tôi sẽ phản hồi trong vòng 24 giờ.",
    });

    setFormData({ name: '', email: '', message: '' });
    setIsSubmitting(false);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setFormData(prev => ({
      ...prev,
      [e.target.name]: e.target.value,
    }));
  };

  return (
    <section className="min-h-screen flex items-center py-20 px-4">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-16">
          <h2 className="font-display text-4xl md:text-5xl font-bold text-primary mb-4">
            {t('contact.title')}
          </h2>
          <p className="font-pixel text-lg text-muted-foreground">
            {t('contact.subtitle')}
          </p>
          <div className="w-20 h-1 bg-gradient-primary mx-auto mt-4"></div>
        </div>

        <div className="grid lg:grid-cols-2 gap-12">
          {/* Contact Form */}
          <PixelCard variant="glow">
            <PixelCardHeader>
              <PixelCardTitle className="text-2xl">
                Gửi tin nhắn
              </PixelCardTitle>
            </PixelCardHeader>
            <PixelCardContent>
              <form onSubmit={handleSubmit} className="space-y-6">
                <div>
                  <label className="block font-pixel text-sm font-bold text-foreground mb-2">
                    {t('contact.form.name')}
                  </label>
                  <input
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    required
                    className="w-full p-3 border-2 border-border bg-background text-foreground font-pixel focus:border-primary focus:outline-none transition-colors"
                    placeholder="Nhập tên của bạn"
                  />
                </div>

                <div>
                  <label className="block font-pixel text-sm font-bold text-foreground mb-2">
                    {t('contact.form.email')}
                  </label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    required
                    className="w-full p-3 border-2 border-border bg-background text-foreground font-pixel focus:border-primary focus:outline-none transition-colors"
                    placeholder="email@example.com"
                  />
                </div>

                <div>
                  <label className="block font-pixel text-sm font-bold text-foreground mb-2">
                    {t('contact.form.message')}
                  </label>
                  <textarea
                    name="message"
                    value={formData.message}
                    onChange={handleChange}
                    required
                    rows={5}
                    className="w-full p-3 border-2 border-border bg-background text-foreground font-pixel focus:border-primary focus:outline-none transition-colors resize-none"
                    placeholder="Mô tả chi tiết về dự án của bạn..."
                  />
                </div>

                <PixelButton 
                  type="submit" 
                  variant="hero" 
                  size="lg" 
                  className="w-full"
                  disabled={isSubmitting}
                >
                  <Send size={20} />
                  {isSubmitting ? 'Đang gửi...' : t('contact.form.send')}
                </PixelButton>
              </form>
            </PixelCardContent>
          </PixelCard>

          {/* Contact Info */}
          <div className="space-y-6">
            <div className="text-center lg:text-left">
              <h3 className="font-display text-2xl font-bold text-secondary mb-4">
                Thông tin liên hệ
              </h3>
              <p className="font-pixel text-muted-foreground">
                Liên hệ trực tiếp qua các kênh bên dưới hoặc gửi tin nhắn để tôi có thể hỗ trợ bạn tốt nhất.
              </p>
            </div>

            <div className="space-y-4">
              {contactInfo.map((info, index) => {
                const Icon = info.icon;
                return (
                  <PixelCard 
                    key={info.label} 
                    variant="hover"
                    className="animate-fade-in-up cursor-pointer"
                    style={{ animationDelay: `${index * 0.1}s` }}
                    onClick={() => window.open(info.href, '_blank')}
                  >
                    <PixelCardContent className="flex items-center gap-4 p-4">
                      <div className="w-12 h-12 bg-primary/10 text-primary flex items-center justify-center border-2 border-primary">
                        <Icon size={24} />
                      </div>
                      <div>
                        <div className="font-pixel font-bold text-foreground">{info.label}</div>
                        <div className="font-pixel text-sm text-muted-foreground">{info.value}</div>
                      </div>
                    </PixelCardContent>
                  </PixelCard>
                );
              })}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}