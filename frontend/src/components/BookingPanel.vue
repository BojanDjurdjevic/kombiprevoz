<script setup>
import { useDisplay } from 'vuetify'
import { useTourStore } from '@/stores/tours'

const tours = useTourStore()
const { mdAndUp } = useDisplay()
</script>

<template>
  <component
    :is="mdAndUp ? 'v-bottom-navigation' : 'v-bottom-sheet'"
    v-model="tours.active"
    :active="tours.active"
    :height="mdAndUp ? 300 : undefined"
    :fullscreen="!mdAndUp"
    class="pa-4"
    color="indigo-darken-4"
  >
    <v-card
      class="w-100 d-flex flex-column flex-md-row justify-space-evenly"
      elevation="9"
      color="indigo-darken-4"
    >
      <!-- SUMMARY -->
      <v-card-title
        v-if="tours.bookedTours.length"
        class="text-center"
      >
        <h3>Dodato:</h3>
        <p>Ukupan iznos: {{ tours.totalPrice }} EUR</p>

        <v-btn
          @click="tours.removeAll"
          color="indigo-lighten-4"
          class="mt-2"
          variant="elevated"
        >
          Isprazni
        </v-btn>
      </v-card-title>

      <!-- LIST -->
      <v-card
        v-for="b in tours.bookedTours"
        :key="b.tour_id"
        class="ma-2 pa-3"
        color="indigo-darken-2"
        elevation="3"
      >
        <v-card-title>{{ b.from }} – {{ b.to }}</v-card-title>
        <v-card-subtitle>{{ b.date }}</v-card-subtitle>
        <v-card-text>Broj mesta: {{ b.places }}</v-card-text>
        <v-card-title>{{ b.price }} EUR</v-card-title>

        <v-card-actions class="justify-center">
          <v-btn
            icon="mdi-delete"
            variant="plain"
            color="red"
            @click="tours.removeTour(b.tour_id)"
          />
        </v-card-actions>
      </v-card>

      <!-- EMPTY -->
      <v-sheet
        v-if="!tours.bookedTours.length"
        class="d-flex align-center justify-center w-100"
      >
        <v-card-title>Nemate izabranih vožnji</v-card-title>
      </v-sheet>

      <!-- ACTIONS -->
      <v-card-actions class="flex-column ga-2">
        <v-btn
          v-if="tours.bookedTours.length"
          color="red-darken-4"
          variant="elevated"
          @click="tours.book"
        >
          Rezerviši
        </v-btn>

        <v-btn
          color="indigo-darken-3"
          variant="elevated"
          @click="tours.active = false"
        >
          Zatvori
        </v-btn>
      </v-card-actions>
    </v-card>
  </component>
</template>
