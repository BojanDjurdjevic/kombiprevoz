<script setup>
defineProps({
  code: String,
  total: [String, Number],
  voucher: String
})
</script>

<template>
  <v-container class="d-flex flex-wrap justify-space-between align-center pa-3">
    <div class="d-flex align-center ga-3">
      <v-btn icon="mdi-keyboard-backspace" to="/rezervacije" />
      <h2>Rezervacija: {{ code }}</h2>
    </div>

    <h2 class="text-green-darken-3">
      Ukupno: {{ total }} EUR
      <v-btn
        icon="mdi-eye"
        color="indigo-darken-3"
        :href="'http://localhost:8080/' + voucher"
        target="_blank"
      />
    </h2>
  </v-container>
</template>
