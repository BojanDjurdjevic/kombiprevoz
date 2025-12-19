<script setup>
import UserLogs from '../admin/UserLogs.vue'
import { useUserStore } from '@/stores/user'

//const emit = defineEmits(['history'])
const user = useUserStore()
</script>

<template>
  <v-container class="text-center">
    <v-dialog v-model="user.profileHistory" max-width="600">
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          color="red-darken-4"
          prepend-icon="mdi-pencil-circle"
          @click="user.profileHistory = true"
        >
          Istorija izmena
        </v-btn>
      </template>

      <UserLogs />
    </v-dialog>
  </v-container>
</template>