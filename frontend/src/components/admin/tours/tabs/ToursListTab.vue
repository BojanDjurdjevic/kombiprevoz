<script setup>
import TourCard from "../parts/TourCard.vue"

defineProps({
  items: { type: Array, required: true }
})

const emit = defineEmits(['open'])
</script>

<template>
  <v-container fluid>
    <v-row>
      <v-col
        v-for="tour in items"
        :key="tour.id"
        cols="12"
        md="6"
        lg="4"
      >
        <TourCard
          :tour="tour"
          @open="emit('open', $event)"
        />
      </v-col>
    </v-row>

    <div
      v-if="!items || items.length === 0"
      class="text-center text-medium-emphasis pa-6"
    >
      Nema rezultata
    </div>
  </v-container>
</template>
