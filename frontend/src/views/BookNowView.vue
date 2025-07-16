<script setup>
import { VNumberInput } from 'vuetify/labs/VNumberInput'
import { useUserStore } from '@/stores/user';
import { useTourStore } from '@/stores/tours';
import { RouterLink, RouterView } from 'vue-router'
import { useSearchStore } from '@/stores/search';

const search = useSearchStore()
const user = useUserStore()
const tours = useTourStore()
/*
onload = () => {
    tours.bookedTours = JSON.parse(localStorage.getItem('myCart'))
} */

</script>

<template>
    
    <v-container class="text-center">
        <h1 class="ma-3" >Vaše odabrane vožnje</h1>
        <v-divider></v-divider>
    </v-container>

    <v-container v-if="tours.bookedTours.length > 0" class="d-flex justify-center">
        <v-container min-height="6rem" class="d-flex justify-space-between align-center
            w-50 flex-wrap" >
            <v-btn
                variant="elevated"
                color="red-darken-4"
                class="ma-2 pa-2"
                min-width="9rem"
                elevation="3"
                @click="tours.removeAll"
            >
                Obrišite <span class="d-none d-md-block"> vožnje</span>
            </v-btn>
            <v-btn
                variant="elevated"
                color="indigo-darken-4"
                class="ma-2 pa-2"
                min-width="9rem"
                elevation="3"
                @click="tours.finishBooking"
            >
                Rezerviši <span class="d-none d-md-block"> vožnje</span>
            </v-btn>
        </v-container>
    </v-container>

    <v-container class="d-flex justify-space-evenly flex-wrap w-100">
        <v-card 
            v-for="t in tours.bookedTours"
            class="pa-6 ma-3 w-lg-50"
            elevation="3"
        >
            <v-card-title class="d-flex justify-center"> {{ t.from }} - {{ t.to }} </v-card-title>
            <v-card-subtitle class="d-flex justify-center"> {{ t.date }} - {{ t.time }} </v-card-subtitle>
            <v-card-title class="d-flex justify-center"> {{ t.price }} EUR</v-card-title>
            <v-divider></v-divider>
            <v-spacer></v-spacer>
            <v-form class="ma-6">
                <v-text-field
                    disabled
                >
                  Ime:  {{ user.user.fullName }}
                </v-text-field>
                <v-label> Adresa Polaska: </v-label>
                <v-combobox 
                    v-model="t.add_from"
                ></v-combobox>
                <v-label>Adresa Dolaska:</v-label>
                <v-combobox
                    v-model="t.add_to"
                ></v-combobox>

                <v-label>Broj mesta:</v-label>
                <v-number-input
                    control-variant="split" 
                    v-model="t.places"
                    :max="t.left"
                    :min="1"
                    v-on:update:model-value="tours.countChangeSeats(t)"
                > 
                    
                </v-number-input>
            </v-form>
            <v-card-actions class="d-flex justify-center">
                <v-btn
                    icon="mdi-delete"
                    @click="tours.removeTour(t.tour_id)"
                >

                </v-btn>
            </v-card-actions>
        </v-card>
    </v-container>

    <v-container v-if="tours.bookedTours.length <= 0" >
        <v-card
            min-height="9rem" class="d-flex flex-column justify-space-evenly align-center pa-1 bg-deep-purple-darken-2"
            elevation="2"
        >
            <v-card-title class="text-center ma-1">
                Nemate odabranih vožnji
            </v-card-title>
            
            <v-divider></v-divider>
            
            <v-card-title>
                Potražite dostupne vožnje pod sekcijom 
                PRETRAGA 
                ili pretražite naše 
                <v-btn
                    variant="text"
                    to="/destinacije"
                >DESTINACIJE</v-btn>     
            </v-card-title>
        </v-card>
    </v-container>

    <v-container class="d-flex justify-center flex-wrap w-100" v-if="tours.bookedTours.length > 0">
        <v-card class="pa-6 ma-3 w-lg-50 text-center"
            elevation="3"
        >
            <v-card-title >
                Ukupna cena vožnji: <h3> {{ tours.totalPrice }}  </h3> 
            </v-card-title>
            
        </v-card>
    </v-container>

    <v-container v-if="tours.bookedTours.length > 0" class="d-flex justify-center">
        <v-container min-height="6rem" class="d-flex justify-space-between align-center
            w-50 flex-wrap" >
            <v-btn
                variant="elevated"
                color="red-darken-4"
                class="ma-2 pa-2"
                min-width="9rem"
                elevation="3"
                @click="tours.removeAll"
            >
                Obrišite <span class="d-none d-md-block"> vožnje</span> 
            </v-btn>
            <v-btn
                variant="elevated"
                color="indigo-darken-4"
                class="ma-2 pa-2"
                min-width="9rem"
                elevation="3"
                @click="tours.finishBooking"
            >
                Rezerviši  <span class="d-none d-md-block"> vožnje</span> 
            </v-btn>
        </v-container>
    </v-container>

</template>