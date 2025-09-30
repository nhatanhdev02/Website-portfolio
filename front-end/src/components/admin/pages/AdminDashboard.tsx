import { useNavigate } from 'react-router-dom';
import { useAdmin } from '@/contexts/AdminContext';
import { PixelCard } from '@/components/admin/ui/PixelCard';
import { PixelBadge } from '@/components/admin/ui/PixelBadge';
import { PixelButton } from '@/components/admin/ui/PixelButton';

export const AdminDashboard: React.FC = () => {
  const navigate = useNavigate();
  const { 
    services, 
    projects, 
    blogPosts, 
    contactMessages,
    user 
  } = useAdmin();

  const unreadMessages = contactMessages.filter(m => !m.read).length;
  const publishedPosts = blogPosts.filter(p => p.status === 'published').length;
  const draftPosts = blogPosts.filter(p => p.status === 'draft').length;

  const stats = [
    { 
      label: 'Services', 
      value: services.length, 
      icon: '⚙️', 
      variant: 'primary' as const,
      description: 'Active services',
      path: '/admin/services'
    },
    { 
      label: 'Projects', 
      value: projects.length, 
      icon: '💼', 
      variant: 'success' as const,
      description: 'Portfolio items',
      path: '/admin/portfolio'
    },
    { 
      label: 'Blog Posts', 
      value: publishedPosts, 
      icon: '📝', 
      variant: 'default' as const,
      description: `${draftPosts} drafts`,
      path: '/admin/blog'
    },
    { 
      label: 'New Messages', 
      value: unreadMessages, 
      icon: '📧', 
      variant: unreadMessages > 0 ? 'warning' as const : 'default' as const,
      description: 'Unread messages',
      path: '/admin/contact'
    },
  ];

  const quickActions = [
    {
      title: 'Edit Hero Section',
      description: 'Update main landing content',
      icon: '🏠',
      path: '/admin/hero',
      variant: 'primary' as const
    },
    {
      title: 'Manage About',
      description: 'Update profile and experience',
      icon: '👤',
      path: '/admin/about',
      variant: 'default' as const
    },
    {
      title: 'Add Service',
      description: 'Create new service offering',
      icon: '⚙️',
      path: '/admin/services',
      variant: 'success' as const
    },
    {
      title: 'New Project',
      description: 'Add portfolio project',
      icon: '💼',
      path: '/admin/portfolio',
      variant: 'default' as const
    },
    {
      title: 'Write Blog Post',
      description: 'Create new content',
      icon: '📝',
      path: '/admin/blog',
      variant: 'default' as const
    },
    {
      title: 'System Settings',
      description: 'Configure preferences',
      icon: '🔧',
      path: '/admin/settings',
      variant: 'default' as const
    }
  ];

  const systemStatus = [
    { label: 'Admin System', status: 'operational', icon: '✓' },
    { label: 'Data Storage', status: 'operational', icon: '✓' },
    { label: 'Authentication', status: 'active', icon: '✓' },
    { label: 'File Upload', status: 'ready', icon: '✓' }
  ];

  const getTimeGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
  };

  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <PixelCard 
        title={`${getTimeGreeting()}, ${user?.username}! 👋`}
        subtitle="Here's an overview of your portfolio content management system."
        variant="primary"
        className="text-center"
      >
        <div className="flex items-center justify-center gap-4 mt-4">
          <PixelBadge variant="success" size="sm">
            System Online
          </PixelBadge>
          <PixelBadge variant="info" size="sm">
            Last login: {user?.loginTime ? new Date(user.loginTime).toLocaleDateString() : 'Today'}
          </PixelBadge>
        </div>
      </PixelCard>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((stat) => (
          <PixelCard
            key={stat.label}
            variant={stat.variant}
            hoverable
            onClick={() => navigate(stat.path)}
            className="cursor-pointer"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-400 mb-1">{stat.label}</p>
                <p className="text-3xl font-bold text-white mb-1">{stat.value}</p>
                <p className="text-xs text-gray-500">{stat.description}</p>
              </div>
              <div className="text-3xl opacity-80">
                {stat.icon}
              </div>
            </div>
          </PixelCard>
        ))}
      </div>

      {/* Quick Actions */}
      <PixelCard title="Quick Actions" subtitle="Jump to common tasks">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {quickActions.map((action) => (
            <div
              key={action.title}
              className="bg-gray-700 border-2 border-gray-600 p-4 hover:bg-gray-600 hover:border-gray-500 transition-all duration-200 cursor-pointer shadow-[0_2px_0_0_#374151] hover:shadow-[0_3px_0_0_#374151] active:translate-y-0.5 active:shadow-[0_1px_0_0_#374151]"
              onClick={() => navigate(action.path)}
            >
              <div className="flex items-center gap-3">
                <span className="text-2xl">{action.icon}</span>
                <div>
                  <h3 className="font-mono text-white font-semibold text-sm">
                    {action.title}
                  </h3>
                  <p className="font-mono text-xs text-gray-400">
                    {action.description}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </PixelCard>

      {/* Recent Activity & System Status */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* System Status */}
        <PixelCard 
          title="System Status" 
          subtitle="All systems operational"
          icon="🔧"
          variant="success"
        >
          <div className="space-y-3">
            {systemStatus.map((item) => (
              <div key={item.label} className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <PixelBadge variant="success" size="sm">
                    {item.icon}
                  </PixelBadge>
                  <span className="text-sm text-gray-300">{item.label}</span>
                </div>
                <PixelBadge variant="success" size="sm">
                  {item.status}
                </PixelBadge>
              </div>
            ))}
          </div>
        </PixelCard>

        {/* Recent Activity */}
        <PixelCard 
          title="Recent Activity" 
          subtitle="Latest content updates"
          icon="📊"
        >
          <div className="space-y-3">
            {unreadMessages > 0 && (
              <div className="flex items-center justify-between p-2 bg-yellow-900 border border-yellow-700 rounded">
                <div className="flex-1">
                  <span className="text-sm text-yellow-200">
                    {unreadMessages} new message{unreadMessages > 1 ? 's' : ''}
                  </span>
                  <div className="text-xs text-yellow-300 mt-1">
                    Requires attention
                  </div>
                </div>
                <PixelButton 
                  size="sm" 
                  variant="secondary"
                  onClick={() => navigate('/admin/contact')}
                >
                  View
                </PixelButton>
              </div>
            )}
            
            {draftPosts > 0 && (
              <div className="flex items-center justify-between p-2 bg-blue-900 border border-blue-700 rounded">
                <div className="flex-1">
                  <span className="text-sm text-blue-200">
                    {draftPosts} draft post{draftPosts > 1 ? 's' : ''} pending
                  </span>
                  <div className="text-xs text-blue-300 mt-1">
                    Ready for review
                  </div>
                </div>
                <PixelButton 
                  size="sm" 
                  variant="secondary"
                  onClick={() => navigate('/admin/blog')}
                >
                  Edit
                </PixelButton>
              </div>
            )}
            
            <div className="flex items-center justify-between p-2 bg-gray-700 border border-gray-600 rounded">
              <div className="flex-1">
                <span className="text-sm text-gray-300">
                  Portfolio: {projects.length} project{projects.length !== 1 ? 's' : ''}
                </span>
                <div className="text-xs text-gray-400 mt-1">
                  {projects.filter(p => p.featured).length} featured
                </div>
              </div>
              <PixelButton 
                size="sm" 
                variant="secondary"
                onClick={() => navigate('/admin/portfolio')}
              >
                Manage
              </PixelButton>
            </div>
            
            <div className="flex items-center justify-between p-2 bg-gray-700 border border-gray-600 rounded">
              <div className="flex-1">
                <span className="text-sm text-gray-300">
                  Services: {services.length} active
                </span>
                <div className="text-xs text-gray-400 mt-1">
                  All operational
                </div>
              </div>
              <PixelButton 
                size="sm" 
                variant="secondary"
                onClick={() => navigate('/admin/services')}
              >
                Edit
              </PixelButton>
            </div>

            {/* Show a message when no activity */}
            {unreadMessages === 0 && draftPosts === 0 && (
              <div className="text-center py-4">
                <div className="text-gray-400 text-sm">
                  🎉 All caught up! No pending tasks.
                </div>
              </div>
            )}
          </div>
        </PixelCard>
      </div>
    </div>
  );
};