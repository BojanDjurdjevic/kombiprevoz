<script setup>
import { useAdminStore } from '@/stores/admin';

const admin = useAdminStore()
</script>

<template>
  <v-card flat class="pa-4">
    <v-divider class="mb-4" />

    <div class="d-flex flex-wrap justify-space-around gap-4 w-100">

      <!-- Aktiviraj -->
      <div v-if="admin.selectedTour?.deleted === 1" class="text-center ma-3">
        <h4>Aktiviraj turu</h4>
        <v-btn
          color="green-darken-3"
          icon="mdi-check-all"
          @click="admin.actions.restoreTour"
        />
      </div>

      <!-- Deaktiviraj -->
      <div v-if="admin.selectedTour?.deleted === 0" class="text-center">
        <h4>Deaktiviraj turu</h4>
        <v-btn
          color="red-darken-3"
          icon="mdi-pause-circle"
          @click="admin.actions.removeTour"
        />
      </div>

      <!-- Trajno brisanje -->
      <div v-if="admin.selectedTour?.deleted === 1" class="text-center ma-3">
        <h4>Zauvek obriši</h4>
        <v-btn
          color="red-darken-4"
          icon="mdi-close-thick"
          @click="admin.actions.permanentDeleteTour"
        />
      </div>

    </div>
  </v-card>
</template>