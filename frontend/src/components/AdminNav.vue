<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { VDateInput } from "vuetify/labs/VDateInput";
import { VNumberInput } from "vuetify/labs/VNumberInput";
import { useUserStore } from "@/stores/user";
import { useAdminStore } from "@/stores/admin";
import { useTourStore } from "@/stores/tours";
import europeCities from "@/data/country-city.json";
import BookingDash from "./BookingDash.vue";
import BookingFilter from "./BookingFilter.vue";
import UserDash from "./UserDash.vue";
import ToursDash from "./ToursDash.vue";
import ChatDash from "./ChatDash.vue";
import DriversDash from "./DriversDash.vue";
import DriversFilter from "./DriversFilter.vue";
import ToursFilter from "./ToursFilter.vue";
import UserFilter from "./UserFilter.vue";
import { useChatStore } from "@/stores/chat";

const user = useUserStore();
const admin = useAdminStore();
const tours = useTourStore();
const chat = useChatStore();

let badgeInterval = null

onMounted(() => {
  chat.loadTickets()
  
  badgeInterval = setInterval(() => {
    if(admin.adminView !== 'Chat') chat.loadTickets()
  }, 30000);
});

onBeforeUnmount(() => {
  if (badgeInterval) {
    clearInterval(badgeInterval)
  }
});

</script>

<template>
  <v-card class="h-100">
    <v-layout class="h-100">
      <v-navigation-drawer expand-on-hover permanent rail>
        <v-list v-if="user.user">
          <v-list-item :subtitle="user.user.email" :title="user.user.name">
            <template v-slot:prepend>
              <v-avatar color="green-darken-3" size="38">
                <span class="text-white font-weight-bold">
                  {{ user.user.initials }}
                </span>
              </v-avatar>
            </template>
          </v-list-item>
        </v-list>

        <v-divider></v-divider>

        <v-list density="compact" nav>
          <v-list-item
            prepend-icon="mdi-book-open-variant"
            title="Rezervacije"
            value="Rezervacije"
            @click="admin.adminView = 'Bookings'"
          ></v-list-item>
          <v-list-item
            prepend-icon="mdi-account-multiple"
            title="Korisnici"
            value="Korisnici"
            @click="admin.adminView = 'Users'"
          ></v-list-item>
          <v-list-item
            prepend-icon="mdi-car"
            title="Vožnje"
            value="Vožnje"
            @click="admin.adminView = 'Tours'"
          ></v-list-item>
          <v-list-item
            prepend-icon="mdi-star"
            title="Chat"
            value="Chat"
            @click="admin.adminView = 'Chat'"
          ></v-list-item>
          <!--
          <v-list-item
            prepend-icon="mdi-star"
            title="Vozači"
            value="Vozači"
            @click="admin.adminView = 'Drivers'"
          ></v-list-item>
          -->
        </v-list>
      </v-navigation-drawer>

      <v-main class="d-flex">
        <v-container class="h-100 w-75 pa-3 d-flex flex-column align-center">
          <div class="w-100 text-center">
            <h1 class="mt-3">{{ admin.adminView }}</h1>
            <v-divider class="w-100"></v-divider>
          </div>
          <!--  BOOKINGS  -->

          <BookingDash v-if="admin.adminView === 'Bookings'" />

          <!--  USERS  -->

          <UserDash v-if="admin.adminView === 'Users'" />

          <!--   TOURS   -->
          
          <ToursDash v-if="admin.adminView === 'Tours'" />

          <!--  DRIVERS  -->
          <DriversDash v-if="admin.adminView === 'Drivers'" />

          <!--  CHAT -->
          <ChatDash v-if="admin.adminView === 'Chat'" />

        </v-container>
        <v-divider vertical></v-divider>

        <!--   FILTERS   -->

        <v-container
          class="w-25 pa-3 d-flex flex-column justify-space-between align-center"
        >
          <div class="w-100 text-center">
            <h1 class="mt-3">Filters</h1>
            <v-divider class="w-100"></v-divider>
          </div>
          <BookingFilter />

          <!--    USERS    -->

          <UserFilter />

          <!--    TOURS    -->

          <ToursFilter />
          
          <!--    DRIVERS    -->

          <DriversFilter />
          
        </v-container>
      </v-main>
    </v-layout>
  </v-card>
</template>
