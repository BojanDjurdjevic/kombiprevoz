<script setup>
import { useMyOrdersStore } from '@/stores/myorders';
const orders = useMyOrdersStore()

</script>

<template>
    <v-container>
        <div class="d-flex justify-space-between ma-2 pa-3">
            <div class="d-flex">
                <v-btn
                    icon="mdi-keyboard-backspace"
                    to="/rezervacije"
                ></v-btn>
                <h1 class="ml-9">Rezervacija broj: 0999</h1>
            </div>
            <h1> Ukupna Cena: {{ orders.oneOrder.price }} </h1>
        </div>
        <v-divider></v-divider>
    </v-container>

    <v-container class="d-flex justify-center">
        <v-card class="w-75 pa-12 rounded-lg d-flex flex-column justify-space-around " height="660" elevation="3">
            <v-card-title class="text-center font-weight-bold"> {{ orders.oneOrder.from }} - {{ orders.oneOrder.to }}</v-card-title>
            <v-divider></v-divider>
            <v-card-title class="d-flex justify-space-between"><p>Adresa Polaska:</p><p> {{ orders.oneOrder.addressFrom }} <v-btn
                    icon="mdi-pencil-circle"
                    variant="plain"
                >
            </v-btn></p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Adresa Dolaska:</p><p> {{ orders.oneOrder.addressTo }} <v-btn
                    icon="mdi-pencil-circle"
                    variant="plain"
                >
            </v-btn></p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Broj Sedišta:</p><p> {{ orders.oneOrder.seats }} <v-btn
                    icon="mdi-pencil-circle"
                    variant="plain"
                >
            </v-btn></p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Datum Vožnje:</p><p> {{ orders.oneOrder.date }} <v-btn
                    icon="mdi-pencil-circle"
                    variant="plain"
                >
            </v-btn></p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Vreme Polaska:</p><p> {{ orders.oneOrder.time }}</p> </v-card-title>
            <v-divider></v-divider>
            <v-card-actions class="d-flex justify-center">
                <v-btn
                    variant="elevated"
                    color="red-darken-4"
                    width="20%"
                >
                    Obriši
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-container>
</template>