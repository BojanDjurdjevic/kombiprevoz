<script setup>
import { useUserStore } from "@/stores/user";
import OrderActions from "./OrderActions.vue";

defineProps({
  order: Object
})

const user = useUserStore()
</script>

<template>
  <v-card class="pa-4 rounded-lg h-100" elevation="3">

    <v-card-title class="text-center font-weight-bold">
      {{ order.from }} – {{ order.to }}
    </v-card-title>

    <v-divider class="my-2" />

    <v-list density="compact">
      <v-list-item title="Polazak" :subtitle="user.user.is_demo ? order.add_from : order.pickup" />
      <v-list-item title="Dolazak" :subtitle="user.user.is_demo ? order.add_to : order.dropoff" />
      <v-list-item title="Datum" :subtitle="order.date" />
      <v-list-item title="Vreme" :subtitle="order.time" />
      <v-list-item title="Broj mesta" :subtitle="order.places" />
      <v-list-item title="Cena" :subtitle="order.price + ' EUR'" />
    </v-list>

    <v-divider class="my-3" />

    <OrderActions
      v-if="!order.deleted"
      :order="order"
    />

    <v-btn
      v-else
      block
      color="red-darken-4"
      disabled
    >
      Obrisano
    </v-btn>

  </v-card>
</template>
