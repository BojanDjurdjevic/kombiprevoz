<script setup>
import { ref } from 'vue';
import { useTourStore } from '@/stores/tours';
import { useMyOrdersStore } from '@/stores/myorders';

const orders = useMyOrdersStore()



</script>

<template>
    <v-container>
        <h1 class="ma-3 pa-1 text-center">Moje Rezervacije</h1>
        <v-divider></v-divider>
    </v-container>
    <!--
    <v-container  >
        <v-row>
            <v-col
                v-for="order in orders.myorders" 
                :key="order"
                cols="12"
                sm="6"
                lg="3"
            >
                <v-card height="18rem" width="18rem" elevation="9"
                    class="d-flex flex-column justify-space-evenly align-center"
                >
                    <v-card-title> {{ order.from }} - {{ order.to }} </v-card-title>
                    <v-divider ></v-divider>
                    <v-card-subtitle> {{ order.date }} </v-card-subtitle>
                    <v-card-subtitle> {{ order.time }} </v-card-subtitle>
                    <v-card-actions>
                        <v-btn
                            variant="elevated"
                            color="red-darken-4"
                        >
                            Uredi
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
    -->

    <v-container class="d-flex justify-space-evenly align-center flex-wrap">
        
            <v-card height="21rem" width="21rem" elevation="9"
                class="d-flex flex-column justify-space-evenly align-center ma-6 rounded-xl"
                
                v-for="order in orders.myorders.orders"
            >
                <v-card class="w-100 text-center
                d-flex flex-column justify-space-evenly align-center
                " height="66%">
                    <v-card-title> {{ order.items[0].from }} - {{ order.items[0].to }} </v-card-title>
                    <v-divider></v-divider>
                    <v-card-subtitle v-if="order.items[0] && order.items[0].deleted == 0">Odlazak: {{ order.items[0].date }} </v-card-subtitle>
                    <v-card-subtitle v-if="order.items[1] && order.items[1].deleted == 0">Dolazak: {{ order.items[1].date }} </v-card-subtitle>
                    <v-divider></v-divider>
                    <v-card-title> {{ order.total }} EUR</v-card-title>
                </v-card>
                <v-card-actions class="w-50 ">
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