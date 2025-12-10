<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useAdminStore } from '@/stores/admin';
import { useChatStore } from '@/stores/chat';
import AdminChatDialog from './AdminChatDialog.vue';

const admin = useAdminStore();
const chat = useChatStore();

const selectedStatus = ref('all');
const chatDialogOpen = ref(false);

const statusOptions = [
  { title: 'Svi', value: 'all' },
  { title: 'Otvoreni', value: 'open' },
  { title: 'U toku', value: 'in_progress' },
  { title: 'Zatvoreni', value: 'closed' }
];

const filteredTickets = computed(() => {
  if (selectedStatus.value === 'all') {
    return chat.tickets;
  }
  return chat.tickets.filter(t => t.status === selectedStatus.value);
});

onMounted(() => {
  handleLoadTickets();
  chat.startTicketPolling();
});

onBeforeUnmount(() => {
  chat.stopTicketPolling();
});

const handleLoadTickets = async () => {
  const filters = {};
  if (selectedStatus.value !== 'all') {
    filters.status = selectedStatus.value;
  }
  await chat.loadTickets(filters);
};

const openTicket = (ticket) => {
  chat.selectTicket(ticket);
  chatDialogOpen.value = true;
};

const handleDialogClosed = () => {
  chatDialogOpen.value = false;
  chat.clearSelectedTicket();
  handleLoadTickets();
};

const getStatusColor = (status) => {
  const colors = {
    open: 'warning',
    in_progress: 'info',
    closed: 'success'
  };
  return colors[status] || 'grey';
};

const getStatusIcon = (status) => {
  const icons = {
    open: 'mdi-email-outline',
    in_progress: 'mdi-message-processing',
    closed: 'mdi-check-circle'
  };
  return icons[status] || 'mdi-message';
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
  <v-container 
    v-if="admin.adminView === 'Chat'" 
    fluid 
    class="h-100 pa-0"
  >
    <v-row class="h-100">
      <v-col cols="12" class="h-100">
        <v-card class="h-100">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-message-text-outline</v-icon>
            <span class="text-h5">Chat Tiketi</span>
            <v-spacer />
            
            <!-- Filter Controls -->
            <v-select
              v-model="selectedStatus"
              :items="statusOptions"
              label="Status"
              density="compact"
              style="max-width: 200px"
              class="mr-2"
              @update:model-value="handleLoadTickets"
            />
            
            <v-btn
              icon="mdi-refresh"
              variant="text"
              @click="handleLoadTickets"
            />
          </v-card-title>

          <v-divider />

          <!-- Loading State -->
          <v-card-text v-if="chat.loading" class="text-center py-8">
            <v-progress-circular indeterminate color="primary" />
            <p class="mt-3 text-medium-emphasis">Učitavanje tiketa...</p>
          </v-card-text>

          <!-- Empty State -->
          <v-card-text v-else-if="filteredTickets.length === 0" class="text-center py-8">
            <v-icon size="64" color="grey-lighten-1">mdi-message-off-outline</v-icon>
            <p class="mt-3 text-h6 text-medium-emphasis">Nema aktivnih tiketa</p>
          </v-card-text>

          <!-- Tickets List -->
          <v-list v-else lines="three" class="overflow-y-auto" style="max-height: calc(100vh - 200px)">
            <template v-for="(ticket, index) in filteredTickets" :key="ticket.id">
              <v-list-item
                :class="['ticket-item', { 'ticket-unread': ticket.unread_count > 0 }]"
                @click="openTicket(ticket)"
              >
                <template #prepend>
                  <v-avatar :color="getStatusColor(ticket.status)" size="48">
                    <v-icon color="white">{{ getStatusIcon(ticket.status) }}</v-icon>
                  </v-avatar>
                </template>

                <v-list-item-title class="font-weight-bold">
                  {{ ticket.customer_name }}
                  <v-chip
                    v-if="ticket.unread_count > 0"
                    color="error"
                    size="x-small"
                    class="ml-2"
                  >
                    {{ ticket.unread_count }}
                  </v-chip>
                </v-list-item-title>

                <v-list-item-subtitle>
                  <div class="d-flex align-center mb-1">
                    <v-icon size="small" class="mr-1">mdi-ticket</v-icon>
                    {{ ticket.ticket_number }}
                    <v-spacer />
                    <v-icon size="small" class="mr-1">mdi-clock-outline</v-icon>
                    {{ chat.formatDateTime(ticket.last_message_time || ticket.created_at) }}
                  </div>
                  
                  <div class="text-truncate" style="max-width: 500px;">
                    {{ ticket.last_message || 'Nema poruka' }}
                  </div>
                </v-list-item-subtitle>

                <template #append>
                  <div class="d-flex flex-column align-end">
                    <v-chip
                      :color="getStatusColor(ticket.status)"
                      size="small"
                      class="mb-2"
                    >
                      {{ getStatusLabel(ticket.status) }}
                    </v-chip>
                    
                    <div v-if="ticket.assigned_admin_name" class="text-caption text-medium-emphasis">
                      <v-icon size="small" class="mr-1">mdi-account</v-icon>
                      {{ ticket.assigned_admin_name }}
                    </div>
                  </div>
                </template>
              </v-list-item>
              
              <v-divider v-if="index < filteredTickets.length - 1" :key="`divider-${ticket.id}`" />
            </template>
          </v-list>

          <!-- Pagination Info -->
          <v-divider v-if="filteredTickets.length > 0" />
          <v-card-actions v-if="filteredTickets.length > 0">
            <v-spacer />
            <span class="text-caption text-medium-emphasis">
              Ukupno: {{ filteredTickets.length }} tiketa
            </span>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- Chat Dialog -->
    <AdminChatDialog
      v-model="chatDialogOpen"
      @closed="handleDialogClosed"
    />
  </v-container>
</template>

<style scoped>
.ticket-item {
  cursor: pointer;
  transition: background-color 0.2s;
}

.ticket-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.ticket-unread {
  background-color: rgba(25, 118, 210, 0.08);
}

.ticket-unread:hover {
  background-color: rgba(25, 118, 210, 0.12);
}
</style>