<script setup>
import { useAdminStore } from '@/stores/admin'
import { useUserStore } from '@/stores/user'
import OrderLogs from '../admin/OrderLogs.vue'

const user = useUserStore()
const admin = useAdminStore()

defineProps({ order: Object })
</script>

<template>
  <v-container class="text-center">
    <v-dialog v-model="admin.orderHistoryDialog" max-width="600">
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          color="red-darken-4"
          prepend-icon="mdi-pencil-circle"
          @click="admin.orderHistoryDialog = true, console.log(order)"
        >
          Istorija izmena
        </v-btn>
      </template>

      <OrderLogs :order="order" />
    </v-dialog>
  </v-container>
</template>