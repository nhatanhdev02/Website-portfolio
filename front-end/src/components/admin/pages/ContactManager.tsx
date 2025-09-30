import React, { useState, useMemo } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { ContactMessage } from '@/types/admin';
import { PixelCard, PixelButton, PixelInput, PixelSelect, PixelBadge, PixelCheckbox } from '@/components/admin/ui';
import { ContactForm } from '@/components/admin/forms/ContactForm';
import { Search, Mail, MailOpen, Trash2, Calendar, User, Filter, X, Settings, MessageSquare, Download, Archive } from 'lucide-react';

interface ContactManagerProps {}

export const ContactManager: React.FC<ContactManagerProps> = () => {
  const { 
    contactMessages, 
    markMessageAsRead, 
    deleteMessage, 
    bulkDeleteMessages,
    bulkMarkAsRead
  } = useAdmin();

  // State for tab management
  const [activeTab, setActiveTab] = useState<'messages' | 'contact-info'>('messages');

  // State for filtering and search
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | 'read' | 'unread'>('all');
  const [dateFilter, setDateFilter] = useState<'all' | 'today' | 'week' | 'month'>('all');
  const [selectedMessages, setSelectedMessages] = useState<string[]>([]);
  const [selectedMessage, setSelectedMessage] = useState<ContactMessage | null>(null);

  // Filter and search messages
  const filteredMessages = useMemo(() => {
    let filtered = [...contactMessages];

    // Apply search filter
    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(message => 
        message.name.toLowerCase().includes(term) ||
        message.email.toLowerCase().includes(term) ||
        message.message.toLowerCase().includes(term)
      );
    }

    // Apply status filter
    if (statusFilter !== 'all') {
      filtered = filtered.filter(message => 
        statusFilter === 'read' ? message.read : !message.read
      );
    }

    // Apply date filter
    if (dateFilter !== 'all') {
      const now = new Date();
      const messageDate = new Date(message.timestamp);
      
      filtered = filtered.filter(message => {
        const msgDate = new Date(message.timestamp);
        
        switch (dateFilter) {
          case 'today':
            return msgDate.toDateString() === now.toDateString();
          case 'week':
            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            return msgDate >= weekAgo;
          case 'month':
            const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
            return msgDate >= monthAgo;
          default:
            return true;
        }
      });
    }

    // Sort by timestamp (newest first)
    return filtered.sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime());
  }, [contactMessages, searchTerm, statusFilter, dateFilter]);

  // Handle message selection
  const handleSelectMessage = (messageId: string) => {
    setSelectedMessages(prev => 
      prev.includes(messageId) 
        ? prev.filter(id => id !== messageId)
        : [...prev, messageId]
    );
  };

  const handleSelectAll = () => {
    if (selectedMessages.length === filteredMessages.length) {
      setSelectedMessages([]);
    } else {
      setSelectedMessages(filteredMessages.map(msg => msg.id));
    }
  };

  // Handle message actions
  const handleViewMessage = (message: ContactMessage) => {
    setSelectedMessage(message);
    if (!message.read) {
      markMessageAsRead(message.id);
    }
  };

  const handleDeleteSelected = () => {
    if (selectedMessages.length > 0) {
      bulkDeleteMessages(selectedMessages);
      setSelectedMessages([]);
    }
  };

  const handleMarkSelectedAsRead = () => {
    const unreadSelected = selectedMessages.filter(id => {
      const message = contactMessages.find(msg => msg.id === id);
      return message && !message.read;
    });
    
    if (unreadSelected.length > 0) {
      bulkMarkAsRead(unreadSelected);
    }
    setSelectedMessages([]);
  };

  // Export messages functionality
  const handleExportMessages = () => {
    const exportData = {
      messages: filteredMessages,
      exportDate: new Date().toISOString(),
      totalMessages: filteredMessages.length,
      filters: {
        searchTerm,
        statusFilter,
        dateFilter
      }
    };
    
    const dataStr = JSON.stringify(exportData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `contact-messages-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  // Auto-cleanup old messages (older than 6 months)
  const handleAutoCleanup = () => {
    const sixMonthsAgo = new Date();
    sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
    
    const oldMessages = contactMessages.filter(msg => 
      new Date(msg.timestamp) < sixMonthsAgo && msg.read
    );
    
    if (oldMessages.length === 0) {
      alert('No old messages found to clean up.');
      return;
    }
    
    const confirmMessage = `This will permanently delete ${oldMessages.length} read messages older than 6 months. Are you sure?`;
    
    if (window.confirm(confirmMessage)) {
      const oldMessageIds = oldMessages.map(msg => msg.id);
      bulkDeleteMessages(oldMessageIds);
      alert(`Successfully deleted ${oldMessages.length} old messages.`);
    }
  };

  // Format date for display
  const formatDate = (date: Date) => {
    return new Date(date).toLocaleDateString('vi-VN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  // Get unread count
  const unreadCount = contactMessages.filter(msg => !msg.read).length;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-white mb-2">Contact Management</h1>
          <p className="text-gray-400">
            Manage contact messages and contact information
            {unreadCount > 0 && (
              <span className="ml-2">
                <PixelBadge variant="warning" size="sm">
                  {unreadCount} unread
                </PixelBadge>
              </span>
            )}
          </p>
        </div>
        
        {activeTab === 'messages' && (
          <div className="flex gap-2">
            <PixelButton
              variant="secondary"
              size="sm"
              onClick={handleExportMessages}
              className="flex items-center gap-2"
            >
              <Download className="w-4 h-4" />
              Export
            </PixelButton>
            
            <PixelButton
              variant="warning"
              size="sm"
              onClick={handleAutoCleanup}
              className="flex items-center gap-2"
            >
              <Archive className="w-4 h-4" />
              Cleanup
            </PixelButton>
          </div>
        )}
      </div>

      {/* Tabs */}
      <PixelCard className="p-4">
        <div className="flex gap-2">
          <PixelButton
            variant={activeTab === 'messages' ? 'primary' : 'secondary'}
            onClick={() => setActiveTab('messages')}
            className="flex items-center gap-2"
          >
            <MessageSquare className="w-4 h-4" />
            Messages
            {unreadCount > 0 && (
              <PixelBadge variant="warning" size="sm">
                {unreadCount}
              </PixelBadge>
            )}
          </PixelButton>
          
          <PixelButton
            variant={activeTab === 'contact-info' ? 'primary' : 'secondary'}
            onClick={() => setActiveTab('contact-info')}
            className="flex items-center gap-2"
          >
            <Settings className="w-4 h-4" />
            Contact Info
          </PixelButton>
        </div>
      </PixelCard>

      {/* Tab Content */}
      {activeTab === 'messages' ? (
        <>
          {/* Filters and Search */}
          <PixelCard className="p-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {/* Search */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <PixelInput
              placeholder="Search messages..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10"
            />
          </div>

          {/* Status Filter */}
          <PixelSelect
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value as typeof statusFilter)}
          >
            <option value="all">All Messages</option>
            <option value="unread">Unread Only</option>
            <option value="read">Read Only</option>
          </PixelSelect>

          {/* Date Filter */}
          <PixelSelect
            value={dateFilter}
            onChange={(e) => setDateFilter(e.target.value as typeof dateFilter)}
          >
            <option value="all">All Time</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
          </PixelSelect>

          {/* Clear Filters */}
          <PixelButton
            variant="secondary"
            onClick={() => {
              setSearchTerm('');
              setStatusFilter('all');
              setDateFilter('all');
            }}
            className="flex items-center gap-2"
          >
            <X className="w-4 h-4" />
            Clear
          </PixelButton>
        </div>
      </PixelCard>

      {/* Bulk Actions */}
      {selectedMessages.length > 0 && (
        <PixelCard className="p-4">
          <div className="flex items-center justify-between">
            <span className="text-white">
              {selectedMessages.length} message(s) selected
            </span>
            <div className="flex gap-2">
              <PixelButton
                variant="primary"
                size="sm"
                onClick={handleMarkSelectedAsRead}
                className="flex items-center gap-2"
              >
                <MailOpen className="w-4 h-4" />
                Mark as Read
              </PixelButton>
              <PixelButton
                variant="danger"
                size="sm"
                onClick={handleDeleteSelected}
                className="flex items-center gap-2"
              >
                <Trash2 className="w-4 h-4" />
                Delete
              </PixelButton>
            </div>
          </div>
        </PixelCard>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Message List */}
        <div className="lg:col-span-2">
          <PixelCard className="p-4">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-bold text-white">Messages</h2>
              <PixelCheckbox
                checked={selectedMessages.length === filteredMessages.length && filteredMessages.length > 0}
                onChange={handleSelectAll}
                label="Select All"
              />
            </div>

            <div className="space-y-2 max-h-96 overflow-y-auto">
              {filteredMessages.length === 0 ? (
                <div className="text-center py-8 text-gray-400">
                  <Mail className="w-12 h-12 mx-auto mb-4 opacity-50" />
                  <p>No messages found</p>
                </div>
              ) : (
                filteredMessages.map((message) => (
                  <div
                    key={message.id}
                    className={`p-3 border-2 rounded cursor-pointer transition-colors ${
                      message.read 
                        ? 'border-gray-600 bg-gray-800/50' 
                        : 'border-blue-600 bg-blue-900/20'
                    } ${
                      selectedMessage?.id === message.id 
                        ? 'ring-2 ring-blue-500' 
                        : 'hover:border-gray-500'
                    }`}
                    onClick={() => handleViewMessage(message)}
                  >
                    <div className="flex items-start gap-3">
                      <PixelCheckbox
                        checked={selectedMessages.includes(message.id)}
                        onChange={() => handleSelectMessage(message.id)}
                        onClick={(e) => e.stopPropagation()}
                      />
                      
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-1">
                          {!message.read && (
                            <Mail className="w-4 h-4 text-blue-400" />
                          )}
                          <span className="font-semibold text-white truncate">
                            {message.name}
                          </span>
                          <PixelBadge 
                            variant={message.read ? 'default' : 'primary'} 
                            size="sm"
                          >
                            {message.read ? 'Read' : 'New'}
                          </PixelBadge>
                        </div>
                        
                        <p className="text-sm text-gray-400 mb-1">{message.email}</p>
                        
                        <p className="text-sm text-gray-300 line-clamp-2">
                          {message.message}
                        </p>
                        
                        <div className="flex items-center gap-2 mt-2 text-xs text-gray-500">
                          <Calendar className="w-3 h-3" />
                          {formatDate(message.timestamp)}
                        </div>
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>
          </PixelCard>
        </div>

        {/* Message Details */}
        <div className="lg:col-span-1">
          <PixelCard className="p-4">
            <h2 className="text-lg font-bold text-white mb-4">Message Details</h2>
            
            {selectedMessage ? (
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">
                    From
                  </label>
                  <div className="flex items-center gap-2">
                    <User className="w-4 h-4 text-gray-400" />
                    <span className="text-white">{selectedMessage.name}</span>
                  </div>
                  <p className="text-sm text-gray-400 mt-1">{selectedMessage.email}</p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">
                    Received
                  </label>
                  <div className="flex items-center gap-2">
                    <Calendar className="w-4 h-4 text-gray-400" />
                    <span className="text-white">{formatDate(selectedMessage.timestamp)}</span>
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">
                    Status
                  </label>
                  <PixelBadge 
                    variant={selectedMessage.read ? 'success' : 'warning'}
                    size="sm"
                  >
                    {selectedMessage.read ? 'Read' : 'Unread'}
                  </PixelBadge>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-2">
                    Message
                  </label>
                  <div className="p-3 bg-gray-800 border border-gray-600 rounded text-white whitespace-pre-wrap">
                    {selectedMessage.message}
                  </div>
                </div>

                <div className="flex gap-2">
                  {!selectedMessage.read && (
                    <PixelButton
                      variant="primary"
                      size="sm"
                      onClick={() => markMessageAsRead(selectedMessage.id)}
                      className="flex items-center gap-2"
                    >
                      <MailOpen className="w-4 h-4" />
                      Mark as Read
                    </PixelButton>
                  )}
                  
                  <PixelButton
                    variant="danger"
                    size="sm"
                    onClick={() => {
                      deleteMessage(selectedMessage.id);
                      setSelectedMessage(null);
                    }}
                    className="flex items-center gap-2"
                  >
                    <Trash2 className="w-4 h-4" />
                    Delete
                  </PixelButton>
                </div>
              </div>
            ) : (
              <div className="text-center py-8 text-gray-400">
                <Mail className="w-12 h-12 mx-auto mb-4 opacity-50" />
                <p>Select a message to view details</p>
              </div>
            )}
          </PixelCard>
        </div>
      </div>
        </>
      ) : (
        /* Contact Info Tab */
        <ContactForm />
      )}
    </div>
  );
};