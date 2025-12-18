<script setup>
import { useUserStore } from '@/stores/user'
const user = useUserStore()
</script>

<template>
  <v-container class="d-flex justify-center">
    <v-card class="w-100 w-md-75 pa-4">
      <v-card-title class="text-center">
        <h2>Lični podaci</h2>
      </v-card-title>

      <v-divider />

      <v-list density="compact">
        <v-list-item v-for="(val, key) in {
          Ime: user.user.name,
          Email: user.user.email,
          Grad: user.user.city,
          Adresa: user.user.address,
          Telefon: user.user.phone
        }" :key="key">
          <v-list-item-title>{{ key }}</v-list-item-title>
          <v-list-item-subtitle class="font-weight-bold">
            {{ val }}
          </v-list-item-subtitle>
        </v-list-item>
      </v-list>
    </v-card>
  </v-container>
</template>
