<script setup>
import { useMyOrdersStore } from "@/stores/myorders";
const orders = useMyOrdersStore();

defineProps({ order: Object })
//defineProps({ orderId: Number })

//@click="orders.deleteRequest(orderId)"
</script>

<template>
  <v-dialog v-model="orders.delDialog" max-width="400">
    <template #activator="{ props }">
      <v-btn
        v-bind="props"
        block
        color="red-darken-4"
        @click="orders.deleteRequest(order)"
      >
        Obriši
      </v-btn>
    </template>

    <v-card>
      <v-card-title>
        Da li ste sigurni da želite da obrišete rezervaciju?
      </v-card-title>

      <v-card-actions>
        <v-btn color="success" @click="orders.actions.cancel">
          Potvrdi
        </v-btn>
        <v-btn color="error" @click="orders.deleteDeny">
          Odustani
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
