<script setup>
import { useDestStore } from '@/stores/destinations';


const dest = useDestStore()

defineProps({
  country: Object
})

const getCountryImage = (country) => dest.getCountryImage(country)
</script>

<template>
  <v-card class="d-flex align-center justify-space-between" height="6rem">
    <v-img
      :src="getCountryImage(country)"
      height="100%"
      contain
      class="d-flex align-center"
    >
      <v-card-title>{{ country.name }}</v-card-title>
    </v-img>
    <v-btn color="indigo-darken-4" icon @click="$emit('edit')">
      <v-icon>mdi-pencil</v-icon>
    </v-btn>
  </v-card>
</template>
