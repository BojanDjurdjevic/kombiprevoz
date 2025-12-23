<script setup>
import { useAdminStore } from '@/stores/admin';


const admin = useAdminStore()
/*
defineProps({
  tour: {
    type: Object,
    required: true
  },
  formatDays: {
    type: Function,
    required: true
  }
}) */
</script>

<template>
  <v-card flat class="pa-4">
    <v-card-title class="text-h6 text-center mb-4">
      {{ admin.selectedTour?.from_city }} → {{ admin.selectedTour?.to_city }}
    </v-card-title>

    <v-divider class="mb-4" />

    <v-list density="compact">
      <v-list-item>
        <v-list-item-title>
          <strong>Polasci:</strong>
          {{ admin.formatDepDays(admin.selectedTour?.departures) }}
        </v-list-item-title>
      </v-list-item>

      <v-list-item>
        <v-list-item-title>
          <strong>Vreme polaska:</strong>
          {{ admin.selectedTour?.time }}
        </v-list-item-title>
      </v-list-item>

      <v-list-item>
        <v-list-item-title>
          <strong>Trajanje:</strong>
          {{ admin.selectedTour?.duration }} h
        </v-list-item-title>
      </v-list-item>

      <v-list-item>
        <v-list-item-title>
          <strong>Maksimum mesta:</strong>
          {{ admin.selectedTour?.seats }}
        </v-list-item-title>
      </v-list-item>

      <v-list-item>
        <v-list-item-title>
          <strong>Cena:</strong>
          {{ admin.selectedTour?.price }} €
        </v-list-item-title>
      </v-list-item>
    </v-list>

    <v-alert
      v-if="admin.selectedTour?.deleted"
      type="error"
      variant="tonal"
      class="mt-4"
    >
      Ova ruta je trenutno deaktivirana
    </v-alert>
  </v-card>
</template>
