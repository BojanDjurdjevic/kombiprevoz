<script setup>
import { useMyOrdersStore } from "@/stores/myorders";
import { useSearchStore } from "@/stores/search";

const orders = useMyOrdersStore();
const search = useSearchStore();

defineProps({ order: Object })
</script>

<template>
  <v-dialog v-model="orders.dateDialog" max-width="600">
    <template #activator="{ props }">
      <v-btn
        v-bind="props"
        block
        color="indigo-darken-4"
        @click="orders.prepareDates(order.from, order.to, order.id)"
      >
        Promeni datum
      </v-btn>
    </template>

    <v-card>
      <v-card-title>Novi datum</v-card-title>

      <v-card-text>
        <v-date-input
          label="Polazak"
          :allowed-dates="search.isDateAllowed"
          @update:model-value="orders.onRequestDate"
        />
        <v-date-input
          label="Povratak"
          :allowed-dates="search.isDateInAllowed"
          @update:model-value="orders.onRequestDateIn"
        />
      </v-card-text>

      <v-card-actions>
        <v-btn color="success" @click="orders.actions.reschedule">
          Potvrdi
        </v-btn>
        <v-btn color="error" @click="orders.clsReschedule">
          Odustani
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
