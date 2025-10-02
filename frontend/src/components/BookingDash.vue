<script setup>
import { useAdminStore } from '@/stores/admin';
import { ref } from 'vue';
    

const admin = useAdminStore()
const tab_bookings = ref(null);

const items_bookings = [
  "Pretraga",
  "U narednih 24h",
  "U narednih 48h"
];
</script>

<template>
  <div
    class="mt-6 pa-3 w-100 d-flex flex-column align-center"
    v-if="admin.adminView == 'Bookings'"
  >
    <v-card class="w-100">
      <v-toolbar>
        <template v-slot>
          <v-tabs v-model="tab_bookings" align-tabs="title">
            <v-tab
              v-for="item in items_bookings"
              :key="item"
              :text="item"
              :value="item"
              @click="admin.actions.fetchBookings(item)"
            ></v-tab>
          </v-tabs>
        </template>
      </v-toolbar>

      <v-tabs-window v-model="tab_bookings">
        <v-tabs-window-item
          v-for="item in items_bookings"
          :key="item"
          :value="item"
        >
          <v-card flat>
            <v-card-text>
              <div class="w-100" v-if="tab_bookings == 'Pretraga'">
                Pretraga
              </div>
              <div class="w-100" v-if="tab_bookings == 'U narednih 24h'">
                24 H
              </div>
              <div class="w-100" v-if="tab_bookings == 'U narednih 48h'">
                48 H
              </div>
            </v-card-text>
          </v-card>
        </v-tabs-window-item>
      </v-tabs-window>
    </v-card>
  </div>
</template>
