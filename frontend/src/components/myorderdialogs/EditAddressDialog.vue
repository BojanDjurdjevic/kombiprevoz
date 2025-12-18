<script setup>
import { useMyOrdersStore } from "@/stores/myorders";
import { useUserStore } from "@/stores/user";

const orders = useMyOrdersStore();
const user = useUserStore();

defineProps({ order: Object })
</script>

<template>
  <v-dialog v-model="orders.addressDialog" max-width="500">
    <template #activator="{ props }">
      <v-btn
        v-bind="props"
        color="success"
        block
        @click="orders.populatePickup(order)"
      >
        Izmeni adrese
      </v-btn>
    </template>

    <v-card>
      <v-form @submit.prevent="orders.actions.addUpdate(orders.pickup, order)">
        <v-card-title>Izmena adrese</v-card-title>

        <v-card-text>
          <v-text-field
            label="Adresa polaska"
            v-model="orders.pickup.add_from"
            :rules="[user.rules.required]"
          />
          <v-text-field
            label="Adresa dolaska"
            v-model="orders.pickup.add_to"
            :rules="[user.rules.required]"
          />
        </v-card-text>

        <v-card-actions>
          <v-btn color="success" type="submit">Potvrdi</v-btn>
          <v-btn color="error" @click="orders.clearPickup">Obriši</v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-dialog>
</template>
