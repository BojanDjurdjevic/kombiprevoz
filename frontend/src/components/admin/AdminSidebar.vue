<script setup>
import { ref } from 'vue'
import { useDisplay } from 'vuetify'
import { useAdminStore } from '@/stores/admin'
import { useUserStore } from '@/stores/user'

const admin = useAdminStore()
const user = useUserStore()

const { mdAndUp, smAndDown } = useDisplay()
const drawer = ref(mdAndUp.value)

// close DRAWER on mobile - onCLick
function go(view) {
  admin.adminView = view
  if (smAndDown.value) drawer.value = false
}
</script>

<template>
  <v-navigation-drawer
    v-model="drawer"
    :permanent="mdAndUp"
    :temporary="smAndDown"
    width="260"
  >
    <!-- Header -->
    <v-list-item class="py-4">
      <v-avatar color="primary" class="mr-3">
        {{ user.user.initials }}
      </v-avatar>

      <div>
        <div class="font-weight-medium">{{ user.user.name }}</div>
        <small class="text-grey">{{ user.user.status }}</small>
      </div>
    </v-list-item>

    <v-divider />

    <!-- Menu -->
    <v-list nav density="comfortable">
      <v-list-item
        prepend-icon="mdi-calendar"
        title="Rezervacije"
        @click="go('Bookings')"
      />

      <v-list-item
        prepend-icon="mdi-account-group"
        title="Korisnici"
        @click="go('Users')"
      />

      <v-list-item
        prepend-icon="mdi-cog"
        title="Tours"
        @click="go('Tours')"
      />

      <v-list-item
        prepend-icon="mdi-chat"
        title="Chat"
        @click="go('Chat')"
      />
    </v-list>
  </v-navigation-drawer>
</template>
