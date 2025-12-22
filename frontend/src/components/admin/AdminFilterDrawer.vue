<script setup>
import { useAdminStore } from '@/stores/admin'
import { useDisplay } from 'vuetify'
import BookingFilter from '../BookingFilter.vue';
import UserFilter from '../UserFilter.vue';
import ToursFilter from '../ToursFilter.vue';

const admin = useAdminStore()
const { mdAndUp } = useDisplay()
</script>

<template>
  <v-navigation-drawer
    v-model="admin.filter"
    location="right"
    temporary
    width="320"
    v-if="admin.adminView !== 'Chat'"
  >
    <h3 class="text-center">Filteri</h3>
    <BookingFilter v-if="admin.adminView === 'Bookings'" />
    <UserFilter v-else-if="admin.adminView === 'Users'" />
    <ToursFilter v-else-if="admin.adminView === 'Tours'" />
  </v-navigation-drawer>
</template>
