<script setup>
import { useMyOrdersStore } from '@/stores/myorders';
const orders = useMyOrdersStore()

</script>

<template>
    <v-container>
        <h1 class="text-center ma-3">Rezervacija broj: 0999</h1>
        <v-divider></v-divider>
    </v-container>

    <v-container>
        <v-card>
            <v-card-title> {{ orders.oneOrder.from }} </v-card-title>
            <v-card-title> {{ orders.oneOrder.to }} </v-card-title>
        </v-card>
    </v-container>
</template>