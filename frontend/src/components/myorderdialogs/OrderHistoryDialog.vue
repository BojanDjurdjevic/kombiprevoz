<script setup>
import { useAdminStore } from '@/stores/admin'
import { useUserStore } from '@/stores/user'
import OrderLogs from '../admin/OrderLogs.vue'
import { useMyOrdersStore } from '@/stores/myorders'

const user = useUserStore()
const myorder = useMyOrdersStore()
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
          @click="myorder.openMyOrderLogs(order)"
        >
          Istorija
        </v-btn>
      </template>

      <OrderLogs :order="order" />
    </v-dialog>
  </v-container>
</template>