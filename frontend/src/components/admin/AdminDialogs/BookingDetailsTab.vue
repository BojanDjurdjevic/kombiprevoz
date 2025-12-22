<script setup>
import { useAdminStore } from '@/stores/admin'
import { useDisplay } from 'vuetify'

const admin = useAdminStore()
const { smAndDown } = useDisplay()
</script>

<template>
  <v-container fluid class="pa-0">

    <v-row dense>
      <!-- ROUTE INFO -->
      <v-col cols="12" md="6">
        <v-card variant="outlined">
          <v-card-title>Ruta</v-card-title>
          <v-card-text class="space-y-2">
            <div><strong>Gradovi:</strong> {{ admin.selected?.from_city }} → {{ admin.selected?.to_city }}</div>
            <div><strong>Datum:</strong> {{ admin.formatDate(admin.selected?.date) }}</div>
            <div><strong>Vreme:</strong> {{ admin.selected?.pickuptime }}</div>
            <div><strong>Trajanje:</strong> {{ admin.selected?.duration }}</div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- ADDRESSES -->
      <v-col cols="12" md="6">
        <v-card variant="outlined">
          <v-card-title>Adrese</v-card-title>
          <v-card-text class="space-y-2">
            <div><strong>Polazak:</strong> {{ admin.selected?.pickup }}</div>
            <div><strong>Dolazak:</strong> {{ admin.selected?.dropoff }}</div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- USER INFO -->
      <v-col cols="12" md="6">
        <v-card variant="outlined">
          <v-card-title>Korisnik</v-card-title>
          <v-card-text class="space-y-2">
            <div><strong>Ime:</strong> {{ admin.selected?.user }}</div>
            <div><strong>Email:</strong> {{ admin.selected?.email }}</div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- PAYMENT -->
      <v-col cols="12" md="6">
        <v-card variant="outlined">
          <v-card-title>Detalji rezervacije</v-card-title>
          <v-card-text class="space-y-2">
            <div><strong>Broj mesta:</strong> {{ admin.selected?.places }}</div>
            <div><strong>Cena:</strong> {{ admin.selected?.price }} €</div>

            <v-chip
              class="mt-2"
              :color="admin.selected?.deleted ? 'red' : 'green'"
              size="small"
            >
              {{ admin.selected?.deleted ? 'Obrisana' : 'Aktivna' }}
            </v-chip>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

  </v-container>
</template>
