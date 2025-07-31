<script setup>
import { useMyOrdersStore } from '@/stores/myorders';
import { useUserStore } from '@/stores/user';
const orders = useMyOrdersStore()
const user = useUserStore()

</script>

<template>
    <v-container>
        <div class="d-flex justify-space-between ma-2 pa-3">
            <div class="d-flex">
                <v-btn
                    icon="mdi-keyboard-backspace"
                    to="/rezervacije"
                ></v-btn>
                <h1 class="ml-9">Rezervacija broj: {{ orders.oneOrder.code }}</h1>
            </div>
            <h1> Ukupna Cena: {{ orders.oneOrder.total }} </h1>
        </div>
        <v-divider></v-divider>
    </v-container>

    <v-container class="d-flex flex-column justify-space-evenly align-center" >
        <v-card class="w-75 ma-3 pa-12 rounded-lg d-flex flex-column justify-space-around " height="660" elevation="3" 
        v-for="order in orders.oneOrder.items"
        >
            <v-card-title class="text-center font-weight-bold"> {{ order.from }} - {{ order.to }}</v-card-title>
            <v-divider></v-divider>
            <v-card-title class="d-flex justify-space-between"><p>Adresa Polaska:</p><p> {{ order.pickup }}</p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Adresa Dolaska:</p><p> {{ order.dropoff }}</p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Broj Sedišta:</p><p> {{ order.places }} </p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Datum Vožnje:</p><p> {{ order.date }} </p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Vreme Polaska:</p><p> {{ order.time }}</p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Cena:</p><p> {{ order.price }}</p> </v-card-title>
            <v-divider></v-divider>
            <v-card-actions class="d-flex justify-center">
                <v-dialog
                    v-model="orders.addressDialog"
                    max-width="75%"
                    transition="dialog-transition"
                >
                    <template v-slot:activator="{props: activatorProps}">
                        <v-btn
                            variant="elevated"
                            color="success"
                            width="20%"
                            prepend-icon="mdi-pencil-circle"
                            v-bind="activatorProps"
                            @click="orders.pickup.id = order.id"
                        >
                            Adrese
                        </v-btn>
                    </template>
                    <v-card>
                        <v-form @submit.prevent="orders.actions.addUpdate(orders.pickup)">
                            <v-card-title primary-title>
                                Izmena adrese 
                            </v-card-title>
                            <v-card-text>
                                <v-text-field
                                    name="Adresa polaska"
                                    label="Adresa polaska"
                                    clearable
                                    v-model="orders.pickup.addFrom"
                                    :rules="[user.rules.required, user.rules.validStr]"
                                ></v-text-field>
                                <v-text-field
                                    name="Adresa dolaska"
                                    label="Adresa dolaska"
                                    clearable
                                    v-model="orders.pickup.addTo"
                                    :rules="[user.rules.required, user.rules.validStr]"
                                ></v-text-field>
                            </v-card-text>
                            <v-card-actions>
                                <v-btn color="success"
                                    type="submit"
                                >Potvrdi</v-btn>
                                <v-btn color="error"
                                    @click="orders.clearPickup"
                                >Obriši</v-btn>
                                <v-btn color="error"
                                    @click="orders.addressDialog = false"
                                >Zatvori</v-btn>
                            </v-card-actions>
                        </v-form>
                    </v-card>
                </v-dialog>
                
                <v-btn
                    variant="elevated"
                    color="indigo-darken-4"
                    width="20%"
                    prepend-icon="mdi-pencil-circle"
                    @click="console.log(order.id)"
                >
                    Broj mesta
                </v-btn>
                <v-btn
                    variant="elevated"
                    color="indigo-darken-4"
                    width="20%"
                    prepend-icon="mdi-pencil-circle"
                    @click="console.log(order.id)"
                >
                    Datum
                </v-btn>
                <v-btn
                    variant="elevated"
                    color="red-darken-4"
                    width="20%"
                    @click="console.log(order.id)"
                >
                    Obriši
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-container>
</template>