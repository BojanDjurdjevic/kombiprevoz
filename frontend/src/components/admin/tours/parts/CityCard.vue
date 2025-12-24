<script setup>
import { useDestStore } from '@/stores/destinations';


const dest = useDestStore()

defineProps({
  city: Object
})

const getCityImage = (city) => dest.getCityPrimaryImage(city)
</script>

<template>
  <v-card class="d-flex justify-space-between align-center" height="3rem">
    <v-img :src="getCityImage(city)" height="100%" cover>
      <v-card-title>{{ city.name }}</v-card-title>
      <v-badge v-if="city.deleted_city" color="red-darken-3" content="NEAKTIVAN" bordered></v-badge>
    </v-img>
    <v-btn color="indigo-darken-4" icon @click="$emit('edit')">
      <v-icon>mdi-pencil</v-icon>
    </v-btn>
  </v-card>
</template>
