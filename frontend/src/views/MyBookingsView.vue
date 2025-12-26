<script setup>
import { ref } from 'vue';
import { useTourStore } from '@/stores/tours';
import { useMyOrdersStore } from '@/stores/myorders';
import { useUserStore } from '@/stores/user';

const orders = useMyOrdersStore()
const user = useUserStore()


</script>

<template>
    <v-container>
        <h1 class="ma-3 pa-1 text-center">Moje Rezervacije</h1>
        <v-divider></v-divider>
    </v-container>

    <v-container class="d-flex justify-space-evenly align-center flex-wrap">
        
            <v-card height="21rem" width="21rem" elevation="9"
                class="d-flex flex-column justify-space-evenly align-center ma-6 rounded-xl"
                v-if="!user.user?.is_demo"
                v-for="order in orders.myorders.orders"
            >
                <v-card v-if="!user.user?.is_demo"
                    class="w-100 text-center
                    d-flex flex-column justify-space-evenly align-center" 
                    height="66%"
                >
                    <v-card-title> {{ order.items[0].from }} - {{ order.items[0].to }} </v-card-title>
                    <v-divider></v-divider>
                    <v-card-subtitle v-if="order.items[0] && order.items[0].deleted == 0">Odlazak: {{ order.items[0].date }} </v-card-subtitle>
                    <v-card-subtitle v-if="order.items[1] && order.items[1].deleted == 0">Dolazak: {{ order.items[1].date }} </v-card-subtitle>
                    <v-divider></v-divider>
                    <v-card-title> {{ order.total }} EUR</v-card-title>
                </v-card>
                <v-card-actions class="w-50 " v-if="!user.user?.is_demo">
                    <v-btn
                        variant="elevated"
                        color="red-darken-4"
                        class="w-100"
                        @click="orders.takeOrder(order)"
                        :to="{name: 'uredi'}"
                    >
                        Uredi
                    </v-btn>
                </v-card-actions>
            </v-card>
            <v-card height="21rem" width="21rem" elevation="9"
                class="d-flex flex-column justify-space-evenly align-center ma-6 rounded-xl"
                v-else
                v-for="order in orders.myorders"
            >
                <v-card v-if="user.user?.is_demo"
                    class="w-100 text-center
                    d-flex flex-column justify-space-evenly align-center" 
                    height="66%"
                >
                    <v-card-title> {{ order.orders.create[0].from }} - {{ order.orders.create[0].to }} </v-card-title>
                    <v-divider></v-divider>
                    <v-card-subtitle >Polazak: {{ order.orders.create[0].date }} </v-card-subtitle>
                    <v-card-subtitle v-if="order.orders.create[1]">Povratak: {{ order.orders.create[1].date }} </v-card-subtitle>
                    <v-divider></v-divider>
                    <v-card-title> {{ order.orders.create[0].price }} EUR</v-card-title>
                    <p> {{ order.id }} </p>
                </v-card>
                <v-card-actions class="w-50 " v-if="user.user?.is_demo">
                    <v-btn
                        variant="elevated"
                        color="red-darken-4"
                        class="w-100"
                        @click="orders.takeOrder(order)"
                        :to="{name: 'uredi'}"
                    >
                        Uredi
                    </v-btn>
                </v-card-actions>
            </v-card>
    </v-container>
</template>