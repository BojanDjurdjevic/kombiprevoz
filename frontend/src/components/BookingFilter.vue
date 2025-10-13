<script setup>
import { useAdminStore } from "@/stores/admin";
import { VDateInput } from "vuetify/labs/VDateInput";
import { VNumberInput } from "vuetify/labs/VNumberInput";
import { ref, onMounted } from "vue";
import { useUserStore } from "@/stores/user";

const admin = useAdminStore();
const user = useUserStore();

onMounted(async () => {
  await admin.actions.fetchAllTours()
})
</script>

<template>
  <v-container
    class="w-100 h-100 d-flex flex-column justify-space-between align-center"
    v-if="admin.adminView == 'Bookings'"
  >
    <div class="pa-6 w-100 h-100 d-flex flex-column">
      <div class="ma-1">
        <v-text-field
          prepend-icon="mdi-book-open-variant"
          v-model="admin.bCode"
          :label="admin.bNum"
          clearable
        ></v-text-field>
      </div>
      <div class="ma-1">
        <v-date-input
          v-model="admin.depDay.date"
          label="Datum vožnje"
          clearable
          :disabled="admin.bCode"
        ></v-date-input>
      </div>
      <div class="ma-1">
        <v-select
          prepend-icon="mdi-highway"
          clearable
          :items="admin.tours"
          item-title="name"
          item-value="id"
          label="Izaberi Rutu"
          v-model="admin.tourID"
          return-object
          :disabled="admin.bCode"
        ></v-select>
      </div>
      <div class="ma-1">
        <v-select
          prepend-icon="mdi-city-variant"
          clearable
          :items="admin.cities.from"
          label="Grad polaska"
          v-model="admin.dep_city"
          :disabled="admin.bCode"
        ></v-select>
      </div>
      <div class="ma-1">
        <v-select
          prepend-icon="mdi-city-variant"
          clearable
          :items="admin.cities.to"
          label="Grad dolaska"
          v-model="admin.arr_city"
          :disabled="admin.bCode"
        ></v-select>
      </div>
      <div class="ma-1">
        <v-text-field
          prepend-icon="mdi-email"
          v-model="admin.usrEmail"
          label="Email korisnika"
          clearable
          type="email"
          :rules="[user.rules.email]"
          :disabled="admin.bCode"
        ></v-text-field>
      </div>
    </div>
    <div
      class="w-100 h-25 d-flex flex-column align-center justify-space-evenly"
    >
      <v-btn 
        class="w-75" 
        prepend-icon="mdi-magnify" 
        color="green-darken-3"
        @click="admin.actions.searchBooking"
      >Traži</v-btn>
      <v-btn
        class="w-75"
        prepend-icon="mdi-close-circle-multiple"
        color="red-darken-3"
        @click="admin.actions.clearBookingSearch"
        >Obriši sve</v-btn>
    </div>
  </v-container>
</template>
