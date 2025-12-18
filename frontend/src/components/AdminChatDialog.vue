<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue';
import { useChatStore } from '@/stores/chat';
import { useUserStore } from '@/stores/user';
import api from '@/api';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  }
});

const emit = defineEmits(['update:modelValue', 'closed']);

// Stores
const chatStore = useChatStore();
const user = useUserStore();

// Refs
const newMessage = ref('');
const messagesContainerRef = ref(null);

const quickEmojis = ['👍', '👎', '😊', '😢', '🎉', '✅', '❌', '⚠️', '💡', '🔥'];

// Local polling control
let adminMessagePollingActive = false
let adminMessagePollingTimeout = null

//Typing indicator polling
let typingPollingTimeout = null
const customerTyping = ref(false)

// Watchers
watch(() => props.modelValue, (newVal) => {
  if (newVal && chatStore.selectedTicket) {
    console.log('Dialog opened - starting admin message polling')
    startAdminMessagePolling()
  } else {
    console.log('Dialog closed - stopping admin message polling')
    stopAdminMessagePolling()
  }
});

watch(() => chatStore.messages.length, () => {
  nextTick(() => scrollToBottom())
})

// Lifecycle
onBeforeUnmount(() => {
  stopAdminMessagePolling()
  stopTypingPolling()
});

// Admin Message Polling
const pollAdminMessages = async () => {
  if (!adminMessagePollingActive || !chatStore.selectedTicket) {
    console.log('Admin poll stopped');
    return;
  }

  try {
    const response = await api.getChat({
      params: {
        data: {
          chat: {
            poll_messages: true,
            ticket_id: chatStore.selectedTicket.id,
            last_message_id: chatStore.lastMessageId
          }
        }
      }
    });

    if (response.data.success) {
      const data = response.data.data

      if (data.ticket_closed) {
        chatStore.selectedTicket.status = 'closed'
        stopAdminMessagePolling()
        return
      }

      if (data.messages && data.messages.length > 0) {
        data.messages.forEach(newMsg => {
          const exists = chatStore.messages.find(m => {
            if (m._temp && m.message === newMsg.message) return true
            return m.id === newMsg.id
          });

          if (!exists) {
            chatStore.messages.push(newMsg)
          }
        });

        //update lastMessageId
        const realMessages = chatStore.messages.filter(m => !m._temp)
        if (realMessages.length > 0) {
          const sortedReal = realMessages.sort((a, b) => a.id - b.id)
          chatStore.lastMessageId = sortedReal[sortedReal.length - 1].id
          chatStore.playNotificationSound()
        }

        //Mark as read
        await chatStore.markAsRead('admin')

        nextTick(() => scrollToBottom())
      }
    }
  } catch (error) {
    console.error('Admin polling error:', error)
  } finally {
    //Continue polling
    if (adminMessagePollingActive && props.modelValue) {
      adminMessagePollingTimeout = setTimeout(() => pollAdminMessages(), 2000) // 2s
    }
  }
};

const startAdminMessagePolling = () => {
  stopAdminMessagePolling()
  adminMessagePollingActive = true;
  pollAdminMessages()
  startTypingPolling() 
};

const stopAdminMessagePolling = () => {
  adminMessagePollingActive = false
  if (adminMessagePollingTimeout) {
    clearTimeout(adminMessagePollingTimeout)
    adminMessagePollingTimeout = null
  }
};

//Typing Indicator Polling
const pollTyping = async () => {
  if (!props.modelValue || !chatStore.selectedTicket) {
    return
  }

  try {
    const response = await api.getChat({
      params: {
        data: {
          chat: {
            get_typing: true,
            ticket_id: chatStore.selectedTicket.id
          }
        }
      }
    });

    if (response.data.success) {
      const typingData = response.data.data.typing
      
      // typing only if user is cust (not admin)
      if (typingData && typingData.user_type === 'customer') {
        customerTyping.value = true
      } else {
        customerTyping.value = false
      }
    }
  } catch (error) {
    console.error('Typing polling error:', error)
  } finally {
    if (props.modelValue) {
      typingPollingTimeout = setTimeout(() => pollTyping(), 1500) // 1.5s
    }
  }
};

const startTypingPolling = () => {
  stopTypingPolling()
  pollTyping()
};

const stopTypingPolling = () => {
  if (typingPollingTimeout) {
    clearTimeout(typingPollingTimeout)
    typingPollingTimeout = null
  }
  customerTyping.value = false
}

// Methods
const handleSendMessage = async () => {
  if (!newMessage.value.trim()) return;

  const messageText = newMessage.value;
  newMessage.value = '';

  const result = await chatStore.sendAdminMessage(
    messageText, 
    user.user.id,
    user.user.name,
    user.user.status
  )
  
  if (!result.success) {
    newMessage.value = messageText;
    alert(result.error || 'Greška pri slanju poruke. Pokušajte ponovo.');
  }
};

const handleTyping = () => {
  chatStore.updateTyping('admin', user.user.id);
};

const handleAssignToMe = async () => {
  if (!chatStore.selectedTicket || !user.user) return;

  const result = await chatStore.assignTicket(
    chatStore.selectedTicket.id, 
    user.user.id
  );
  
  if (result.success) {
    chatStore.selectedTicket.assigned_to = user.user.id;
    chatStore.selectedTicket.assigned_admin_name = user.user.name;
  } else {
    alert(result.error || 'Greška pri preuzimanju tiketa');
  }
};

const handleCloseTicket = async () => {
  if (!chatStore.selectedTicket || !user.user) return;

  if (!confirm('Da li ste sigurni da želite da zatvorite ovaj tiket?')) {
    return;
  }

  const result = await chatStore.closeTicket(
    chatStore.selectedTicket.id,
    user.user.id
  );
  
  if (result.success) {
    emit('closed');
  } else {
    alert(result.error || 'Greška pri zatvaranju tiketa');
  }
};

const handleReopenTicket = async () => {
  if (!chatStore.selectedTicket) return;

  const result = await chatStore.reopenTicket(chatStore.selectedTicket.id)
  
  if (result.success) {
    chatStore.selectedTicket.status = 'in_progress'
    startAdminMessagePolling()
  } else {
    alert(result.error || 'Greška pri ponovnom otvaranju tiketa')
  }
}

const addEmoji = (emoji) => {
  newMessage.value += emoji;
};

const closeDialog = () => {
  stopAdminMessagePolling()
  stopTypingPolling()
  emit('update:modelValue', false);
  emit('closed');
};

const scrollToBottom = () => {
  const container = messagesContainerRef.value?.$el || messagesContainerRef.value
  if (container) {
    container.scrollTop = container.scrollHeight
  }
};

const getStatusColor = (status) => {
  const colors = {
    open: 'warning',
    in_progress: 'info',
    closed: 'success'
  };
  return colors[status] || 'grey';
};

const getStatusLabel = (status) => {
  const labels = {
    open: 'Otvoren',
    in_progress: 'U toku',
    closed: 'Zatvoren'
  };
  return labels[status] || status;
};
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    max-width="800"
    persistent
    scrollable
  >
    <v-card v-if="chatStore.selectedTicket" class="chat-dialog">
      <!-- Header -->
      <v-card-title class="bg-primary text-white d-flex align-center py-3">
        <div class="flex-grow-1">
          <div class="text-h6">{{ chatStore.selectedTicket.customer_name }}</div>
          <div class="text-caption">{{ chatStore.selectedTicket.ticket_number }}</div>
        </div>
        
        <v-chip
          :color="getStatusColor(chatStore.selectedTicket.status)"
          size="small"
          class="mr-2"
        >
          {{ getStatusLabel(chatStore.selectedTicket.status) }}
        </v-chip>

        <v-btn
          icon="mdi-close"
          variant="text"
          @click="closeDialog"
        />
      </v-card-title>

      <!-- Customer Info -->
      <v-card-text class="pa-0">
        <v-expansion-panels variant="accordion">
          <v-expansion-panel>
            <v-expansion-panel-title>
              <v-icon class="mr-2">mdi-information-outline</v-icon>
              Informacije o korisniku
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <v-list density="compact">
                <v-list-item>
                  <template #prepend>
                    <v-icon>mdi-email</v-icon>
                  </template>
                  <v-list-item-title>Email</v-list-item-title>
                  <v-list-item-subtitle>{{ chatStore.selectedTicket.customer_email }}</v-list-item-subtitle>
                </v-list-item>

                <v-list-item v-if="chatStore.selectedTicket.customer_phone">
                  <template #prepend>
                    <v-icon>mdi-phone</v-icon>
                  </template>
                  <v-list-item-title>Telefon</v-list-item-title>
                  <v-list-item-subtitle>{{ chatStore.selectedTicket.customer_phone }}</v-list-item-subtitle>
                </v-list-item>

                <v-list-item v-if="chatStore.selectedTicket.reservation_number">
                  <template #prepend>
                    <v-icon>mdi-bookmark</v-icon>
                  </template>
                  <v-list-item-title>Broj rezervacije</v-list-item-title>
                  <v-list-item-subtitle>{{ chatStore.selectedTicket.reservation_number }}</v-list-item-subtitle>
                </v-list-item>

                <v-list-item>
                  <template #prepend>
                    <v-icon>mdi-clock-outline</v-icon>
                  </template>
                  <v-list-item-title>Kreiran</v-list-item-title>
                  <v-list-item-subtitle>{{ chatStore.formatFullDateTime(chatStore.selectedTicket.created_at) }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>
      </v-card-text>

      <v-divider />

      <!-- Messages Area -->
      <v-card-text
        ref="messagesContainerRef"
        class="messages-container pa-4"
      >
        <div
          v-for="msg in chatStore.sortedMessages"
          :key="msg.id"
          :class="['message', msg.sender_type === 'admin' ? 'message-own' : 'message-other']"
        >
          <div class="message-bubble" :class="{ 'message-sending': msg._temp }">
            <div v-if="msg.sender_type === 'admin'" class="message-sender">
              {{ msg.admin_name }} ({{ msg.admin_role }})
            </div>
            <div class="message-text">{{ msg.message }}</div>
            <div class="message-time">
              {{ chatStore.formatTime(msg.created_at) }}
              <v-icon v-if="msg._temp" size="x-small" class="ml-1" color="grey">
                mdi-clock-outline
              </v-icon>
              <v-icon v-if="msg.is_read && msg.sender_type === 'admin'" size="x-small" class="ml-1">
                mdi-check-all
              </v-icon>
            </div>
          </div>
        </div>

        <!-- Typing Indicator -->
        <div v-if="customerTyping" class="message message-other">
          <div class="message-bubble typing-indicator">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>

        <!-- Loading Indicator -->
        <div v-if="chatStore.loadingMessages" class="text-center py-4">
          <v-progress-circular indeterminate size="32" color="primary" />
        </div>
      </v-card-text>

      <v-divider />

      <!-- Input Area -->
      <v-card-actions class="pa-3">
        <v-text-field
          v-model="newMessage"
          placeholder="Unesite poruku..."
          variant="outlined"
          density="comfortable"
          hide-details
          :disabled="chatStore.selectedTicket.status === 'closed' || chatStore.sending"
          @keyup.enter="handleSendMessage"
          @input="handleTyping"
        >
          <template #prepend-inner>
            <v-menu>
              <template #activator="{ props }">
                <v-btn
                  icon="mdi-emoticon-outline"
                  variant="text"
                  size="small"
                  v-bind="props"
                />
              </template>
              <v-card>
                <v-card-text class="emoji-picker">
                  <span
                    v-for="emoji in quickEmojis"
                    :key="emoji"
                    class="emoji-item"
                    @click="addEmoji(emoji)"
                  >
                    {{ emoji }}
                  </span>
                </v-card-text>
              </v-card>
            </v-menu>
          </template>

          <template #append-inner>
            <v-btn
              icon="mdi-send"
              color="primary"
              variant="text"
              :disabled="!newMessage.trim() || chatStore.selectedTicket.status === 'closed'"
              :loading="chatStore.sending"
              @click="handleSendMessage"
            />
          </template>
        </v-text-field>
      </v-card-actions>

      <v-divider />

      <!-- Actions -->
      <v-card-actions class="pa-3 bg-grey-lighten-4">
        <v-btn
          v-if="!chatStore.selectedTicket.assigned_to"
          color="info"
          variant="tonal"
          prepend-icon="mdi-hand-back-right"
          :loading="chatStore.assigning"
          @click="handleAssignToMe"
        >
          Preuzmi tiket
        </v-btn>

        <v-chip v-else size="small" color="info">
          <v-icon start>mdi-account</v-icon>
          {{ chatStore.selectedTicket.assigned_admin_name }}
        </v-chip>

        <v-spacer />

        <v-btn
          v-if="chatStore.selectedTicket.status !== 'closed'"
          color="success"
          variant="tonal"
          prepend-icon="mdi-check-circle"
          :loading="chatStore.closing"
          @click="handleCloseTicket"
        >
          Zatvori tiket
        </v-btn>

        <v-btn
          v-else
          color="warning"
          variant="tonal"
          prepend-icon="mdi-refresh"
          @click="handleReopenTicket"
        >
          Ponovo otvori
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.chat-dialog {
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.messages-container {
  height: 500px;
  max-height: 500px;
  overflow-y: auto;
  background-color: #f5f5f5;
  flex: 1;
}

.message {
  margin-bottom: 12px;
  display: flex;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message-own {
  justify-content: flex-end;
}

.message-other {
  justify-content: flex-start;
}

.message-bubble {
  max-width: 70%;
  padding: 10px 14px;
  border-radius: 18px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.message-own .message-bubble {
  background-color: #1976d2;
  color: white;
  border-bottom-right-radius: 4px;
}

.message-other .message-bubble {
  background-color: white;
  color: #333;
  border-bottom-left-radius: 4px;
}

.message-sender {
  font-size: 11px;
  font-weight: 600;
  margin-bottom: 4px;
  opacity: 0.9;
}

.message-text {
  font-size: 14px;
  line-height: 1.4;
  word-wrap: break-word;
  white-space: pre-wrap;
}

.message-time {
  font-size: 11px;
  margin-top: 4px;
  opacity: 0.7;
  display: flex;
  align-items: center;
}

/* Typing Indicator */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 12px 16px;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  background-color: #999;
  border-radius: 50%;
  animation: typing 1.4s infinite;
}

.message-sending {
  opacity: 0.7;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 60%, 100% {
    transform: translateY(0);
  }
  30% {
    transform: translateY(-10px);
  }
}

/* Emoji Picker */
.emoji-picker {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
  padding: 8px;
}

.emoji-item {
  font-size: 24px;
  cursor: pointer;
  text-align: center;
  padding: 8px;
  border-radius: 8px;
  transition: background-color 0.2s;
}

.emoji-item:hover {
  background-color: rgba(0, 0, 0, 0.08);
}

/* Scrollbar */
.messages-container::-webkit-scrollbar {
  width: 8px;
}

.messages-container::-webkit-scrollbar-track {
  background: #e0e0e0;
}

.messages-container::-webkit-scrollbar-thumb {
  background: #999;
  border-radius: 4px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
  background: #666;
}
</style>