<script setup>
import { useAdminStore } from '@/stores/admin'

import BookingDash from '../BookingDash.vue'
import UserDash from '../UserDash.vue'
import ChatDash from '../ChatDash.vue'
import ToursDash from '../ToursDash.vue'
import BookingFilter from '../BookingFilter.vue'
import ToursFilter from '../ToursFilter.vue'
import UserFilter from '../UserFilter.vue'
import { useDisplay } from 'vuetify/lib/framework.mjs'

const admin = useAdminStore()

const { mdAndUp } = useDisplay()
</script>

<template>
  <v-container fluid class="d-flex">
    <!-- Main content -->
    <v-container
      class="pa-3"
      
    >
      <div v-if="admin.adminView === 'Bookings'" >
        <BookingDash  />
      </div>
      <div v-else-if="admin.adminView === 'Users'">
        <UserDash  />
      </div>
      <div v-else-if="admin.adminView === 'Tours'">
        <ToursDash  />
      </div>
      <div v-else-if="admin.adminView === 'Chat'">
        <ChatDash  />
      </div>
    </v-container>
  </v-container>
</template>
