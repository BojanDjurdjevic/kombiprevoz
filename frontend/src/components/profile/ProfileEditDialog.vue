<script setup>
import ProfileEditForm from '../ProfileEditForm.vue'
import { useUserStore } from '@/stores/user'

const emit = defineEmits(['open'])
const user = useUserStore()
</script>

<template>
  <v-container class="text-center">
    <v-dialog v-model="user.profileDialog" max-width="600">
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          color="indigo-darken-4"
          prepend-icon="mdi-pencil-circle"
          @click="emit('open')"
        >
          Uredi podatke
        </v-btn>
      </template>

      <ProfileEditForm />
    </v-dialog>
  </v-container>
</template>
