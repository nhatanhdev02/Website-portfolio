import { useState, useEffect } from 'react';

export type Language = 'vi' | 'en';

interface Translations {
  vi: Record<string, string>;
  en: Record<string, string>;
}

const translations: Translations = {
  vi: {
    // Navigation
    'nav.home': 'Trang chủ',
    'nav.about': 'Giới thiệu',
    'nav.services': 'Dịch vụ',
    'nav.portfolio': 'Portfolio',
    'nav.blog': 'Blog',
    'nav.contact': 'Liên hệ',

    // Hero Section
    'hero.greeting': 'Xin chào! Tôi là',
    'hero.name': 'Nhật Anh Dev',
    'hero.title': 'Freelance Fullstack Developer',
    'hero.subtitle': 'Phát triển web toàn diện với công nghệ hiện đại',
    'hero.cta': 'Xem Portfolio',

    // About Section
    'about.title': 'Giới thiệu',
    'about.description': 'Với hơn 5 năm kinh nghiệm trong lập trình fullstack, tôi chuyên phát triển các ứng dụng web hiện đại sử dụng React, Node.js, và các công nghệ tiên tiến. Đam mê tạo ra những sản phẩm chất lượng cao và trải nghiệm người dùng tuyệt vời.',

    // Services Section
    'services.title': 'Dịch vụ',
    'services.web.title': 'Phát triển Website',
    'services.web.desc': 'Xây dựng website responsive, tối ưu SEO và hiệu suất cao',
    'services.api.title': 'Xây dựng API',
    'services.api.desc': 'Thiết kế và phát triển RESTful API bảo mật, scalable',
    'services.ui.title': 'Thiết kế UI/UX',
    'services.ui.desc': 'Tạo giao diện người dùng đẹp mắt và trải nghiệm tối ưu',
    'services.optimization.title': 'Tối ưu hiệu suất',
    'services.optimization.desc': 'Cải thiện tốc độ tải và hiệu suất ứng dụng web',

    // Portfolio Section
    'portfolio.title': 'Portfolio',
    'portfolio.ecommerce.title': 'E-commerce Platform',
    'portfolio.ecommerce.desc': 'Nền tảng thương mại điện tử với React và Node.js',
    'portfolio.api.title': 'API Management System',
    'portfolio.api.desc': 'Hệ thống quản lý API với dashboard thống kê',

    // Blog Section
    'blog.title': 'Blog',
    'blog.api.title': 'Hướng dẫn xây dựng RESTful API',
    'blog.api.desc': 'Tìm hiểu cách xây dựng API hiệu quả với Node.js và Express',
    'blog.react.title': 'Bí quyết tối ưu React',
    'blog.react.desc': 'Những kỹ thuật tối ưu hiệu suất cho ứng dụng React',

    // Contact Section
    'contact.title': 'Liên hệ',
    'contact.subtitle': 'Sẵn sàng thực hiện dự án của bạn',
    'contact.form.name': 'Tên của bạn',
    'contact.form.email': 'Email',
    'contact.form.message': 'Nội dung tin nhắn',
    'contact.form.send': 'Gửi tin nhắn',
    'contact.email': 'nhatanhdev@gmail.com',
    'contact.phone': '+84 123 456 789',

    // Footer
    'footer.copyright': '© 2024 Nhật Anh Dev. Tất cả quyền được bảo lưu.',
  },
  en: {
    // Navigation
    'nav.home': 'Home',
    'nav.about': 'About',
    'nav.services': 'Services',
    'nav.portfolio': 'Portfolio',
    'nav.blog': 'Blog',
    'nav.contact': 'Contact',

    // Hero Section
    'hero.greeting': 'Hello! I\'m',
    'hero.name': 'Nhat Anh Dev',
    'hero.title': 'Freelance Fullstack Developer',
    'hero.subtitle': 'Comprehensive web development with modern technology',
    'hero.cta': 'View Portfolio',

    // About Section
    'about.title': 'About Me',
    'about.description': 'With over 5 years of experience in fullstack programming, I specialize in developing modern web applications using React, Node.js, and cutting-edge technologies. Passionate about creating high-quality products and excellent user experiences.',

    // Services Section
    'services.title': 'Services',
    'services.web.title': 'Website Development',
    'services.web.desc': 'Building responsive websites with SEO optimization and high performance',
    'services.api.title': 'API Development',
    'services.api.desc': 'Designing and developing secure, scalable RESTful APIs',
    'services.ui.title': 'UI/UX Design',
    'services.ui.desc': 'Creating beautiful user interfaces and optimal user experiences',
    'services.optimization.title': 'Performance Optimization',
    'services.optimization.desc': 'Improving loading speed and web application performance',

    // Portfolio Section
    'portfolio.title': 'Portfolio',
    'portfolio.ecommerce.title': 'E-commerce Platform',
    'portfolio.ecommerce.desc': 'E-commerce platform built with React and Node.js',
    'portfolio.api.title': 'API Management System',
    'portfolio.api.desc': 'API management system with analytics dashboard',

    // Blog Section
    'blog.title': 'Blog',
    'blog.api.title': 'RESTful API Development Guide',
    'blog.api.desc': 'Learn how to build efficient APIs with Node.js and Express',
    'blog.react.title': 'React Optimization Secrets',
    'blog.react.desc': 'Performance optimization techniques for React applications',

    // Contact Section
    'contact.title': 'Contact',
    'contact.subtitle': 'Ready to bring your project to life',
    'contact.form.name': 'Your Name',
    'contact.form.email': 'Email',
    'contact.form.message': 'Message Content',
    'contact.form.send': 'Send Message',
    'contact.email': 'nhatanhdev@gmail.com',
    'contact.phone': '+84 123 456 789',

    // Footer
    'footer.copyright': '© 2024 Nhat Anh Dev. All rights reserved.',
  },
};

export function useLanguage() {
  const [language, setLanguage] = useState<Language>(() => {
    const saved = localStorage.getItem('language');
    return (saved as Language) || 'vi';
  });

  useEffect(() => {
    localStorage.setItem('language', language);
  }, [language]);

  const t = (key: string): string => {
    return translations[language][key] || key;
  };

  const toggleLanguage = () => {
    setLanguage(prev => prev === 'vi' ? 'en' : 'vi');
  };

  return { language, t, toggleLanguage };
}