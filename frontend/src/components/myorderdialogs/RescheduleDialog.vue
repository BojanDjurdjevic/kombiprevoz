<script setup>
import { ref } from 'vue'
import { useMyOrdersStore } from "@/stores/myorders";
import { useSearchStore } from "@/stores/search";
import { useUserStore } from "@/stores/user";
import { VDateInput } from 'vuetify/labs/VDateInput'

const orders = useMyOrdersStore();
const search = useSearchStore();
const user = useUserStore()

const myOrder = ref([])

myOrder.value = user.user?.is_demo ? orders.oneOrder.items : orders.oneOrder.orders

defineProps({ order: Object })
</script>

<template>
  <v-dialog v-model="orders.dateDialog" max-width="600">
    <template #activator="{ props }">
      <v-btn
        v-if="!user.user?.is_demo"
        v-bind="props"
        block
        color="indigo-darken-4"
        @click="orders.prepareDates(order.from, order.to, order.id)"
      >
        Promeni datume
      </v-btn>
      <v-btn
        v-else
        v-bind="props"
        block
        color="indigo-darken-4"
        @click="orders.prepareDemoDates(order)"
      >
        Promeni datume
      </v-btn>
    </template>

    <v-card>
      <v-card-title>Izaberi nove datume</v-card-title>

      <v-card-text>
        <v-date-input
          label="Unesi novi polazak"
          :allowed-dates="search.isDateAllowed"
          @update:model-value="orders.onRequestDate"
        />
        <v-card-text>
          Trenutni datum polaska: {{ orders.currentDate }}
        </v-card-text>
        <v-date-input
          label="Unesi novi povratak"
          :allowed-dates="search.isDateInAllowed"
          @update:model-value="orders.onRequestDateIn"
        />
        <v-card-text>
          Trenutni datum pvratka: {{ orders.currentDateIn }}
        </v-card-text>
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
