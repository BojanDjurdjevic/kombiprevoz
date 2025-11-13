<script setup>
import { useAdminStore } from "@/stores/admin";
import { ref } from "vue";

const admin = useAdminStore();
</script>

<template>
  <v-container
    class="w-100 h-100 d-flex flex-column justify-space-between align-center"
    v-if="admin.adminView == 'Tours'"
  >
    <div class="pa-6 w-100 h-25 d-flex flex-column">
      <div class="ma-4">
        <v-select
          prepend-icon="mdi-highway"
          clearable
          :items="admin.tours"
          item-title="name"
          item-value="id"
          label="Izaberi Rutu"
          v-model="admin.tourName"
          return-object
        ></v-select>
      </div>
      <div class="ma-4">
        <v-select
          prepend-icon="mdi-city-variant"
          clearable
          :items="admin.cities.from"
          item-title="name"
          item-value="id"
          label="Grad polaska"
          v-model="admin.toursFrom"
          return-object
        ></v-select>
      </div>
      <div class="ma-4">
        <v-select
          prepend-icon="mdi-city-variant"
          clearable
          :items="admin.cities.to"
          item-title="name"
          item-value="id"
          label="Grad dolaska"
          v-model="admin.toursTo"
          return-object
        ></v-select>
      </div>
    </div>
    <div class="w-100 h-75 d-flex flex-column align-center justify-center">
      <v-btn class="w-75 ma-3" prepend-icon="mdi-magnify" color="green-darken-3"
        @click="admin.actions.searchTour"
      >Traži</v-btn
      >
      <v-btn
        class="w-75 ma-3"
        prepend-icon="mdi-close-circle-multiple"
        color="red-darken-3"
        @click="admin.actions.clearTourFilters"
        >Obriši sve</v-btn
      >
    </div>
  </v-container>
</template>
