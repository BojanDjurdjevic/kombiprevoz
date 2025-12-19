<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { useChatStore } from '@/stores/chat';
import api from '@/api';

// Store
const chatStore = useChatStore();

// Refs
const formValid = ref(false)
const initialMessage = ref('')
const newMessage = ref('')
const infoFormRef = ref(null)
const messagesContainerRef = ref(null)

//Typing polling
let typingPollingTimeout = null
const adminTyping = ref(false)

// Validation Rules
const rules = {
  required: v => !!v || 'Ovo polje je obavezno',
  email: v => /.+@.+\..+/.test(v) || 'Email mora biti validan'
};

// Lifecycle
onMounted(() => {
  chatStore.loadSavedTicket()
})

onBeforeUnmount(() => {
  chatStore.stopMessagePolling()
  stopTypingPolling()
})

// Watch for new messages to scroll
watch(() => chatStore.messages.length, () => {
  nextTick(() => scrollToBottom())
})

// Methods

//poll typing status
const pollTyping = async () => {
  if (!chatStore.isOpen || !chatStore.hasActiveTicket) {
    return;
  }

  try {
    const response = await api.getChat({
      params: {
        data: {
          chat: {
            get_typing: true,
            ticket_id: chatStore.ticketId
          }
        }
      }
    })

    if (response.data.success) {
      const typingData = response.data.data.typing
      
      // typing show only if admin (not customer)
      if (typingData && typingData.user_type === 'admin') {
        adminTyping.value = true
      } else {
        adminTyping.value = false
      }
    }
  } catch (error) {
    console.error('Typing polling error:', error)
  } finally {
    if (chatStore.isOpen) {
      typingPollingTimeout = setTimeout(() => pollTyping(), 1500) // 1.5s
    }
  }
}

const startTypingPolling = () => {
  stopTypingPolling()
  pollTyping()
}

const stopTypingPolling = () => {
  if (typingPollingTimeout) {
    clearTimeout(typingPollingTimeout)
    typingPollingTimeout = null
  }
  adminTyping.value = false
}

const openChat = () => {
  chatStore.isOpen = true
  if (chatStore.hasActiveTicket) {
    chatStore.startMessagePolling()
    startTypingPolling()
    nextTick(() => scrollToBottom())
  }
}

const minimizeChat = () => {
  chatStore.isOpen = fals
  chatStore.stopMessagePolling()
  stopTypingPolling()
}

const closeChat = () => {
  chatStore.isOpen = false
  chatStore.stopMessagePolling()
  stopTypingPolling()
}

const handleCreateTicket = async () => {
  console.log('handleCreateTicket STARTED')
  console.log('Form valid:', formValid.value)
  console.log('Customer Info:', chatStore.customerInfo)

  if (!formValid.value) {
    console.log('Form not valid, aborting')
    return;
  } 
  console.log('Calling chatStore.createTicket...')
  const result = await chatStore.createTicket(initialMessage.value);

  console.log('Result from store:', result)
  
  if (result.success) {
    console.log('Success! Clearing form...')
    initialMessage.value = ''
    nextTick(() => scrollToBottom())
  } else {
    console.log('Failed:', result.error)
    alert(result.error || 'Greška pri kreiranju tiketa. Pokušajte ponovo.')
  }
};

const handleSendMessage = async () => {
  if (!newMessage.value.trim()) return

  const messageText = newMessage.value
  newMessage.value = ''

  const result = await chatStore.sendMessage(messageText)
  
  if (!result.success) {
    newMessage.value = messageText
    alert(result.error || 'Greška pri slanju poruke. Pokušajte ponovo.')
  }
};

const handleTyping = () => {
  chatStore.updateTyping('customer')
};

const scrollToBottom = () => {
  const container = messagesContainerRef.value?.$el || messagesContainerRef.value
  if (container) {
    container.scrollTop = container.scrollHeight
  }
}

const startNewConversation = () => {
  console.log('Starting new conversation...')
  
  chatStore.resetCustomerChat()

  chatStore.isOpen = true
}


</script>

<template>
  <div class="chat-widget">
    <!-- Chat Button -->
    <v-btn
      v-if="!chatStore.isOpen"
      color="primary"
      icon="mdi-message-text"
      size="x-large"
      class="chat-trigger-btn"
      @click="openChat"
      elevation="6"
    >
      <v-badge
        v-if="chatStore.unreadCount > 0"
        :content="chatStore.unreadCount"
        color="error"
        floating
      >
        <v-icon>mdi-message-text</v-icon>
      </v-badge>
      <v-icon v-else>mdi-message-text</v-icon>
    </v-btn>

    <!-- Chat Window -->
    <v-card
      v-if="chatStore.isOpen"
      class="chat-window"
      elevation="10"
    >
      <!-- Header -->
      <v-card-title class="bg-primary text-white py-3 d-flex align-center">
        <span class="text-h6">Kontaktirajte nas</span>
        <v-spacer />
        <v-btn
          icon="mdi-minus"
          variant="text"
          density="comfortable"
          @click="minimizeChat"
        />
        <v-btn
          icon="mdi-close"
          variant="text"
          density="comfortable"
          @click="closeChat"
        />
      </v-card-title>

      <!-- Info Form -->
      <v-card-text v-if="!chatStore.hasActiveTicket" class="pa-4">
        <v-form ref="infoFormRef" v-model="formValid">
          <p class="text-body-2 mb-4 text-medium-emphasis">
            Molimo popunite sledeće informacije pre nego što započnete razgovor:
          </p>

          <v-text-field
            v-model="chatStore.customerInfo.name"
            label="Ime i prezime *"
            :rules="[rules.required]"
            variant="outlined"
            density="comfortable"
            class="mb-2"
          />

          <v-text-field
            v-model="chatStore.customerInfo.email"
            label="Email *"
            :rules="[rules.required, rules.email]"
            variant="outlined"
            density="comfortable"
            class="mb-2"
          />

          <v-text-field
            v-model="chatStore.customerInfo.phone"
            label="Broj telefona"
            variant="outlined"
            density="comfortable"
            class="mb-2"
          />

          <v-text-field
            v-model="chatStore.customerInfo.reservation"
            label="Broj rezervacije"
            hint="Unesite broj rezervacije ako ga imate, ili 'NO'"
            persistent-hint
            variant="outlined"
            density="comfortable"
            class="mb-2"
          />

          <v-textarea
            v-model="initialMessage"
            label="Vaša poruka"
            variant="outlined"
            rows="3"
            density="comfortable"
            class="mb-3"
          />

          <v-btn
            color="primary"
            block
            :disabled="!formValid"
            :loading="chatStore.creating"
            @click="handleCreateTicket"
          >
            Započni razgovor
          </v-btn>
        </v-form>
      </v-card-text>

      <!-- Chat Area -->
      <div v-else>
        <!-- Messages Container -->
        <v-card-text 
          ref="messagesContainerRef"
          class="messages-container pa-4"
        >
          <div
            v-for="msg in chatStore.sortedMessages"
            :key="msg.id"
            :class="['message', msg.sender_type === 'customer' ? 'message-own' : 'message-other']"
          >
            <div class="message-bubble" :class="{ 'message-sending': msg._temp }">
              <div v-if="msg.sender_type === 'admin'" class="message-sender">
                {{ msg.admin_name || 'Admin' }}
              </div>
              <div class="message-text">{{ msg.message }}</div>
              <div class="message-time">
                {{ chatStore.formatTime(msg.created_at) }}

                <v-icon v-if="msg._temp" size="x-small" class="ml-1" color="grey">
                  mdi-clock-outline
                </v-icon>
                <v-icon v-else-if="!msg._temp && msg.sender_type === 'customer'" size="x-small" class="ml-1">
                  mdi-check
                </v-icon>
              </div>
            </div>
          </div>

          <!-- Typing Indicator -->
          <div v-if="adminTyping" class="message message-other">
            <div class="message-bubble typing-indicator">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>

          <!-- Closed Notice -->
          <v-alert
            v-if="chatStore.ticketClosed"
            type="info"
            variant="tonal"
            density="compact"
            class="mt-3"
          >
            <p class="mb-3">Razgovor je zatvoren.</p>
            <v-btn
              color="primary"
              variant="elevated"
              prepend-icon="mdi-message-plus"
              @click="startNewConversation"
            >
              Započni novi razgovor
            </v-btn>
          </v-alert>
        </v-card-text>

        <!-- Input Area -->
        <v-divider />
        <v-card-actions class="pa-3">
          <v-text-field
            v-model="newMessage"
            placeholder="Unesite poruku..."
            variant="outlined"
            density="comfortable"
            hide-details
            :disabled="chatStore.ticketClosed"
            @keyup.enter="handleSendMessage"
            @input="handleTyping"
          >
            <template #append-inner>
              <v-btn
                icon="mdi-send"
                color="primary"
                variant="text"
                :disabled="!newMessage.trim() || chatStore.ticketClosed"
                :loading="chatStore.sending"
                @click="handleSendMessage"
              />
            </template>
          </v-text-field>
        </v-card-actions>
      </div>
    </v-card>
  </div>
</template>


<style scoped>
.chat-widget {
  position: relative;
}

.chat-trigger-btn {
  position: fixed !important;
  bottom: 20px;
  right: 20px;
  z-index: 999;
}

.chat-window {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 400px;
  max-width: calc(100vw - 40px);
  max-height: calc(100vh - 100px);
  z-index: 1000;
  display: flex;
  flex-direction: column;
}

.messages-container {
  height: 400px;
  max-height: 400px;
  overflow-y: auto;
  background-color: #f5f5f5;
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
  color: #666;
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

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

.message-sending {
  opacity: 0.7;
}

@keyframes typing {
  0%, 60%, 100% {
    transform: translateY(0);
  }
  30% {
    transform: translateY(-10px);
  }
}

/* Scrollbar Styling */
.messages-container::-webkit-scrollbar {
  width: 6px;
}

.messages-container::-webkit-scrollbar-track {
  background: #e0e0e0;
}

.messages-container::-webkit-scrollbar-thumb {
  background: #999;
  border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
  background: #666;
}

@media (max-width: 600px) {
  .chat-window {
    width: calc(100vw - 20px);
    right: 10px;
    bottom: 10px;
  }
  
  .messages-container {
    height: 300px;
    max-height: 300px;
  }
}
</style>