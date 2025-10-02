<script setup>
import { useAdminStore } from "@/stores/admin";
import { ref } from "vue";

const admin = useAdminStore();
</script>

<template>
  <v-container
    class="w-100 h-100 d-flex flex-column justify-space-between align-center"
    v-if="admin.adminView == 'Drivers'"
  >
    <div class="pa-6 w-100 h-100 d-flex flex-column">
      <div class="ma-4">
        <v-text-field
          prepend-icon="mdi-email"
          v-model="admin.usrEmail"
          label="Email vozača"
          clearable
          type="email"
        ></v-text-field>
      </div>
    </div>
    <div
      class="w-100 h-25 d-flex flex-column align-center justify-space-evenly"
    >
      <v-btn class="w-75" prepend-icon="mdi-magnify" color="green-darken-3"
        >Traži</v-btn
      >
      <v-btn
        class="w-75"
        prepend-icon="mdi-close-circle-multiple"
        color="red-darken-3"
        >Obriši sve</v-btn
      >
    </div>
  </v-container>
</template>
