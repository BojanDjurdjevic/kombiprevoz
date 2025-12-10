import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import api from '@/api';

export const useChatStore = defineStore('chat', () => {
  // ==================== STATE ====================
  
  const ticketId = ref(null);
  const ticketNumber = ref(null);
  const messages = ref([]);
  const lastMessageId = ref(0);
  const unreadCount = ref(0);
  const ticketClosed = ref(false);
  const otherTyping = ref(false);
  
  const customerInfo = ref({
    name: '',
    email: '',
    phone: '',
    reservation: ''
  });
  
  const tickets = ref([]);
  const selectedTicket = ref(null);
  const lastChecked = ref(new Date().toISOString());
  
  const isOpen = ref(false);
  const creating = ref(false);
  const sending = ref(false);
  const loading = ref(false);
  const loadingMessages = ref(false);
  const assigning = ref(false);
  const closing = ref(false);
  
  let messagePollingInterval = null;
  let ticketPollingInterval = null;
  let typingTimeout = null;

  // ==================== COMPUTED ====================
  
  const hasActiveTicket = computed(() => !!ticketId.value);
  
  const sortedMessages = computed(() => {
    return [...messages.value].sort((a, b) => 
      new Date(a.created_at) - new Date(b.created_at)
    );
  });
  
  const openTickets = computed(() => 
    tickets.value.filter(t => t.status === 'open')
  );
  
  const inProgressTickets = computed(() => 
    tickets.value.filter(t => t.status === 'in_progress')
  );
  
  const closedTickets = computed(() => 
    tickets.value.filter(t => t.status === 'closed')
  );
  
  const totalUnreadCount = computed(() => 
    tickets.value.reduce((sum, ticket) => sum + (ticket.unread_count || 0), 0)
  );

  // ==================== CUSTOMER ACTIONS ====================
  
  const loadSavedTicket = () => {
    const savedId = localStorage.getItem('chatTicketId');
    const savedNumber = localStorage.getItem('chatTicketNumber');
    
    if (savedId) {
      ticketId.value = parseInt(savedId);
      ticketNumber.value = savedNumber;
      loadMessages();
    }
  };
  
  const createTicket = async (initialMessage = '') => {
    creating.value = true;
    
    try {
      const response = await api.sendChat({
        chat: {
          create_ticket: true,
          customer_name: customerInfo.value.name,
          customer_email: customerInfo.value.email,
          customer_phone: customerInfo.value.phone,
          reservation_number: customerInfo.value.reservation,
          initial_message: initialMessage
        }
      });

      if (response.data.success) {
        ticketId.value = response.data.data.ticket_id;
        ticketNumber.value = response.data.data.ticket_number;
        
        localStorage.setItem('chatTicketId', ticketId.value);
        localStorage.setItem('chatTicketNumber', ticketNumber.value);
        
        await loadMessages();
        startMessagePolling();
        
        return { success: true };
      } else {
        return { success: false, error: response.data.error };
      }
    } catch (error) {
      console.error('Error creating ticket:', error);
      return { success: false, error: 'Greška pri kreiranju tiketa' };
    } finally {
      creating.value = false;
    }
  };
  
  const loadMessages = async () => {
    if (!ticketId.value) return;
    
    loadingMessages.value = true;
    
    try {
      const response = await api.getChat({
        params: {
          data: {
            chat: {
              messages: true,
              ticket_id: ticketId.value
            }
          }
        }
      });

      if (response.data.success) {
        messages.value = response.data.data.messages || [];
        
        if (messages.value.length > 0) {
          lastMessageId.value = messages.value[messages.value.length - 1].id;
        }
        
        await markAsRead('customer');
      }
    } catch (error) {
      console.error('Error loading messages:', error);
    } finally {
      loadingMessages.value = false;
    }
  };
  
  const sendMessage = async (message) => {
    if (!message.trim() || !ticketId.value) return { success: false };
    
    sending.value = true;
    
    try {
      const response = await api.sendChat({
        chat: {
          send_message: true,
          ticket_id: ticketId.value,
          sender_type: 'customer',
          sender_id: null,
          message: message
        }
      });

      if (response.data.success) {
        return { success: true };
      } else {
        return { success: false, error: response.data.error };
      }
    } catch (error) {
      console.error('Error sending message:', error);
      return { success: false, error: 'Greška pri slanju poruke' };
    } finally {
      sending.value = false;
    }
  };
  
  const pollMessages = async () => {
    if (!ticketId.value) return;
    
    try {
      const response = await api.getChat({
        params: {
          data: {
            chat: {
              poll_messages: true,
              ticket_id: ticketId.value,
              last_message_id: lastMessageId.value
            }
          }
        }
      });

      if (response.data.success) {
        const data = response.data.data;
        
        if (data.ticket_closed) {
          ticketClosed.value = true;
          stopMessagePolling();
          return;
        }
        
        if (data.messages && data.messages.length > 0) {
          messages.value.push(...data.messages);
          lastMessageId.value = messages.value[messages.value.length - 1].id;
          
          if (!isOpen.value) {
            const adminMessages = data.messages.filter(m => m.sender_type === 'admin');
            unreadCount.value += adminMessages.length;
          } else {
            await markAsRead('customer');
          }
        }
      }
    } catch (error) {
      console.error('Polling error:', error);
    } finally {
      if (isOpen.value && !ticketClosed.value) {
        messagePollingInterval = setTimeout(() => pollMessages(), 1000);
      }
    }
  };
  
  const startMessagePolling = () => {
    stopMessagePolling();
    pollMessages();
  };
  
  const stopMessagePolling = () => {
    if (messagePollingInterval) {
      clearTimeout(messagePollingInterval);
      messagePollingInterval = null;
    }
  };
  
  const updateTyping = (userType, userId = null) => {
    if (!ticketId.value) return;
    
    clearTimeout(typingTimeout);
    
    api.sendChat({
      chat: {
        typing: true,
        ticket_id: ticketId.value,
        user_type: userType,
        user_id: userId
      }
    }).catch(err => console.error('Typing error:', err));
    
    typingTimeout = setTimeout(() => {
      // Typing stopped
    }, 3000);
  };
  
  const markAsRead = async (readerType) => {
    if (!ticketId.value) return;
    
    try {
      await api.sendChat({
        chat: {
          mark_read: true,
          ticket_id: ticketId.value,
          reader_type: readerType
        }
      });
      
      if (readerType === 'customer') {
        unreadCount.value = 0;
      }
    } catch (error) {
      console.error('Error marking as read:', error);
    }
  };
  
  const resetCustomerChat = () => {
    ticketId.value = null;
    ticketNumber.value = null;
    messages.value = [];
    lastMessageId.value = 0;
    unreadCount.value = 0;
    ticketClosed.value = false;
    otherTyping.value = false;
    isOpen.value = false;
    
    localStorage.removeItem('chatTicketId');
    localStorage.removeItem('chatTicketNumber');
    
    stopMessagePolling();
  };

  // ==================== ADMIN ACTIONS ====================
  
  const loadTickets = async (filters = {}) => {
    loading.value = true;
    
    try {
      const response = await api.getChat({
        params: {
          data: {
            chat: {
              admin: {
                tickets: true
              },
              ticket_status: filters.status || null,
              assigned_to: filters.assigned_to || null
            }
          }
        }
      });

      if (response.data.success) {
        tickets.value = response.data.data.tickets || [];
      }
    } catch (error) {
      console.error('Error loading tickets:', error);
    } finally {
      loading.value = false;
    }
  };
  
  const pollNewTickets = async () => {
    try {
      const response = await api.getChat({
        params: {
          data: {
            chat: {
              admin: {
                poll_tickets: true
              },
              last_checked: lastChecked.value
            }
          }
        }
      });

      if (response.data.success) {
        const data = response.data.data;
        
        if (data.tickets && data.tickets.length > 0) {
          data.tickets.forEach(newTicket => {
            const existingIndex = tickets.value.findIndex(t => t.id === newTicket.id);
            if (existingIndex >= 0) {
              tickets.value[existingIndex] = newTicket;
            } else {
              tickets.value.unshift(newTicket);
            }
          });
          
          playNotificationSound();
        }
        
        lastChecked.value = data.timestamp;
      }
    } catch (error) {
      console.error('Polling tickets error:', error);
    } finally {
      ticketPollingInterval = setTimeout(() => pollNewTickets(), 3000);
    }
  };
  
  const startTicketPolling = () => {
    stopTicketPolling();
    pollNewTickets();
  };
  
  const stopTicketPolling = () => {
    if (ticketPollingInterval) {
      clearTimeout(ticketPollingInterval);
      ticketPollingInterval = null;
    }
  };
  
  const loadTicketMessages = async (ticketIdParam) => {
    loadingMessages.value = true;
    
    try {
      const response = await api.getChat({
        params: {
          data: {
            chat: {
              messages: true,
              ticket_id: ticketIdParam
            }
          }
        }
      });

      if (response.data.success) {
        messages.value = response.data.data.messages || [];
        
        if (messages.value.length > 0) {
          lastMessageId.value = messages.value[messages.value.length - 1].id;
        }
        
        await markAsRead('admin');
      }
    } catch (error) {
      console.error('Error loading ticket messages:', error);
    } finally {
      loadingMessages.value = false;
    }
  };
  
  const sendAdminMessage = async (message, senderId) => {
    if (!message.trim() || !selectedTicket.value) return { success: false };
    
    sending.value = true;
    
    try {
      const response = await api.sendChat({
        chat: {
          send_message: true,
          ticket_id: selectedTicket.value.id,
          sender_type: 'admin',
          sender_id: senderId,
          message: message
        }
      });

      if (response.data.success) {
        return { success: true };
      } else {
        return { success: false, error: response.data.error };
      }
    } catch (error) {
      console.error('Error sending admin message:', error);
      return { success: false, error: 'Greška pri slanju poruke' };
    } finally {
      sending.value = false;
    }
  };
  
  const assignTicket = async (ticketIdParam, adminId) => {
    assigning.value = true;
    
    try {
      const response = await api.sendChat({
        chat: {
          admin: {
            assign: true
          },
          ticket_id: ticketIdParam,
          admin_id: adminId
        }
      });

      if (response.data.success) {
        await loadTickets();
        return { success: true };
      } else {
        return { success: false, error: response.data.error };
      }
    } catch (error) {
      console.error('Error assigning ticket:', error);
      return { success: false, error: 'Greška pri preuzimanju tiketa' };
    } finally {
      assigning.value = false;
    }
  };
  
  const closeTicket = async (ticketIdParam, closedBy) => {
    closing.value = true;
    
    try {
      const response = await api.sendChat({
        chat: {
          admin: {
            close: true
          },
          ticket_id: ticketIdParam,
          closed_by: closedBy
        }
      });

      if (response.data.success) {
        await loadTickets();
        return { success: true };
      } else {
        return { success: false, error: response.data.error };
      }
    } catch (error) {
      console.error('Error closing ticket:', error);
      return { success: false, error: 'Greška pri zatvaranju tiketa' };
    } finally {
      closing.value = false;
    }
  };
  
  const reopenTicket = async (ticketIdParam) => {
    try {
      const response = await api.sendChat({
        chat: {
          admin: {
            reopen: true
          },
          ticket_id: ticketIdParam
        }
      });

      if (response.data.success) {
        await loadTickets();
        return { success: true };
      } else {
        return { success: false, error: response.data.error };
      }
    } catch (error) {
      console.error('Error reopening ticket:', error);
      return { success: false, error: 'Greška pri ponovnom otvaranju tiketa' };
    }
  };
  
  const selectTicket = (ticket) => {
    selectedTicket.value = ticket;
    if (ticket) {
      ticketId.value = ticket.id;
      loadTicketMessages(ticket.id);
    }
  };
  
  const clearSelectedTicket = () => {
    selectedTicket.value = null;
    messages.value = [];
    lastMessageId.value = 0;
    stopMessagePolling();
  };

  // ==================== HELPER FUNCTIONS ====================
  
  const formatTime = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleTimeString('sr-RS', { 
      hour: '2-digit', 
      minute: '2-digit' 
    });
  };
  
  const formatDateTime = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 86400000) {
      const hours = Math.floor(diff / 3600000);
      const minutes = Math.floor((diff % 3600000) / 60000);
      
      if (hours === 0) {
        return `Pre ${minutes}min`;
      }
      return `Pre ${hours}h`;
    }
    
    return date.toLocaleDateString('sr-RS', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };
  
  const formatFullDateTime = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleString('sr-RS', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };
  
  const playNotificationSound = () => {
    try {
      const audio = new Audio('/sounds/notification.mp3');
      audio.play().catch(err => console.log('Sound play failed:', err));
    } catch (err) {
      console.log('Audio not available');
    }
  };

  // ==================== RETURN ====================
  
  return {
    // State
    ticketId,
    ticketNumber,
    messages,
    lastMessageId,
    unreadCount,
    ticketClosed,
    otherTyping,
    customerInfo,
    tickets,
    selectedTicket,
    lastChecked,
    isOpen,
    creating,
    sending,
    loading,
    loadingMessages,
    assigning,
    closing,
    
    // Computed
    hasActiveTicket,
    sortedMessages,
    openTickets,
    inProgressTickets,
    closedTickets,
    totalUnreadCount,
    
    // Customer Actions
    loadSavedTicket,
    createTicket,
    loadMessages,
    sendMessage,
    pollMessages,
    startMessagePolling,
    stopMessagePolling,
    updateTyping,
    markAsRead,
    resetCustomerChat,
    
    // Admin Actions
    loadTickets,
    pollNewTickets,
    startTicketPolling,
    stopTicketPolling,
    loadTicketMessages,
    sendAdminMessage,
    assignTicket,
    closeTicket,
    reopenTicket,
    selectTicket,
    clearSelectedTicket,
    
    // Helpers
    formatTime,
    formatDateTime,
    formatFullDateTime,
    playNotificationSound
  };
});