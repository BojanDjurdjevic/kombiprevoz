<script setup>
import { useAdminStore } from '@/stores/admin'

import BookingDash from '../BookingDash.vue'
import UserDash from '../UserDash.vue'
import ChatDash from '../ChatDash.vue'
import ToursDash from '../ToursDash.vue'
import BookingFilter from '../BookingFilter.vue'
//import ToursDash from '../ToursDash.vue'

const admin = useAdminStore()
</script>

<template>
  <v-container fluid class="d-flex">
    <!-- Desktop filter sidebar -->
    <v-container
      v-if="admin.adminView === 'Bookings' && $vuetify.display.mdAndUp"
      class="w-25 pa-3"
    >
      <BookingFilter />
    </v-container>

    <!-- Main content -->
    <v-container
      class="pa-3"
      :class="admin.adminView === 'Bookings' && $vuetify.display.mdAndUp ? 'w-75' : 'w-100'"
    >
      <BookingDash v-if="admin.adminView === 'Bookings'" />
      <UserDash v-else-if="admin.adminView === 'Users'" />
      <ToursDash v-else-if="admin.adminView === 'Tours'" />
      <ChatDash v-else-if="admin.adminView === 'Chat'" />
    </v-container>
  </v-container>
</template>
