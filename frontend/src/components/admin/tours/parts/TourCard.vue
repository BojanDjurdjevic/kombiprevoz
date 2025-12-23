<script setup>
defineProps({
  tour: { type: Object, required: true }
})

const emit = defineEmits(['open'])
</script>

<template>
  <v-card
    class="mb-3"
    elevation="2"
    @click="emit('open', tour)"
  >
    <v-card-title class="text-subtitle-1 font-weight-bold">
      {{ tour.from_city }} → {{ tour.to_city }}
    </v-card-title>

    <v-card-subtitle>
      {{ tour.country }}
    </v-card-subtitle>

    <v-card-text class="text-body-2">
      <div>🕒 {{ tour.time }}</div>
      <div>💺 Mesta: {{ tour.seats }}</div>
      <div v-if="tour.deleted" class="text-red">
        ⛔ Deaktivirana
      </div>
    </v-card-text>
  </v-card>
</template>
