<script setup>
import { useMyOrdersStore } from "@/stores/myorders";
import { VNumberInput } from "vuetify/labs/VNumberInput";

const orders = useMyOrdersStore();
defineProps({ order: Object })
</script>

<template>
  <v-dialog v-model="orders.plsDialog" max-width="400">
    <template #activator="{ props }">
      <v-btn
        v-bind="props"
        color="indigo-darken-4"
        block
        @click="orders.places(order)"
      >
        Broj mesta
      </v-btn>
    </template>

    <v-card>
      <v-card-title>Izaberi broj mesta</v-card-title>

      <v-card-text>
        <v-number-input
          v-model="orders.seatsUp.seats"
          :min="1"
          :max="7"
          control-variant="split"
          @update:model-value="orders.calculateNewPrice"
        />
        <p>Nova cena: {{ orders.newPrice }}</p>
      </v-card-text>

      <v-card-actions>
        <v-btn color="success" @click="orders.actions.changePlaces">
          Potvrdi
        </v-btn>
        <v-btn color="error" @click="orders.clsSeats">Odustani</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
