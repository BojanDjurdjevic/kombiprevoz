<script setup>
import { useAdminStore } from '@/stores/admin';


const admin = useAdminStore()

</script>

<template>
  <v-container fluid class="pa-0">
    <v-card
      v-for="item in items"
      :key="item.tour_id"
      class="mb-3"
      variant="outlined"
    >
      <v-card-title class="d-flex justify-space-between">
        <div>
          <strong>{{ item.from_city }}</strong>
          →
          <strong>{{ item.to_city }}</strong>
        </div>

        <v-chip
          size="small"
          :color="item.passengers >= item.total_places ? 'red' : 'green'"
        >
          {{ item.passengers }}/{{ item.total_places }}
        </v-chip>
      </v-card-title>

      <v-card-subtitle>
        {{ item.date }}
      </v-card-subtitle>

      <v-divider />

      <v-card-actions class="justify-end">
        <v-btn
          size="small"
          variant="tonal"
          
        >
          Detalji
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>
