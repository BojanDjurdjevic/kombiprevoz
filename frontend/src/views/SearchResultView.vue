<script setup>
    import { ref } from 'vue'
    import { useTourStore } from '@/stores/tours';
    import { useUserStore } from '@/stores/user';
    import { VNumberInput } from 'vuetify/labs/VNumberInput'
    import { useDisplay } from 'vuetify/lib/framework.mjs';
    import router from '@/router';
    import BookingPanel from '@/components/BookingPanel.vue';

    const user = useUserStore()
    const tours = useTourStore()
    const { mdAndUp } = useDisplay()
    
    if(localStorage.getItem('avTours')) {
        onload = () => {
            tours.available = JSON.parse(localStorage.getItem('avTours'))
        }
    }
    

</script>

<template>
    <v-container class="text-center">
        <h1>Dostupne vožnje</h1>
    </v-container>
    
    <v-btn 
        class="ma-1"
        @click="tours.active = true"
    >
        Otvori
    </v-btn>

    <v-container fluid class="pa-3">
        <v-row justify="center" class="ga-6">
            
            <v-col
            v-for="t in tours.available"
            :key="t.id"
            cols="12"
            md="10"
            lg="8"
            >
            <v-card elevation="2" rounded="lg">
                <v-row>

                <!-- FROM -->
                <v-col cols="12" md="3">
                    <v-sheet 
                        class="pa-4 text-center h-100 d-flex flex-column align-center justify-center rounded-lg" 
                        color="grey-lighten-3"
                        elevation="9"
                    >
                        <v-card-title>{{ t.from }}</v-card-title>
                        <v-card-subtitle>{{ t.date }}</v-card-subtitle>
                        <v-card-title>{{ t.departure }}</v-card-title>
                    </v-sheet>
                </v-col>

                <!-- ACTIONS -->
                <v-col cols="12" md="6" class="d-flex justify-center">
                    <v-card-actions class="flex-column ga-2">
                    <v-card-title>{{ t.priceTotal }} EUR</v-card-title>

                    <v-number-input
                        v-model="t.seats"
                        :min="1"
                        :max="t.left"
                        control-variant="split"
                        @update:model-value="tours.countSeats(t.id)"
                    />

                    <v-card-text class="text-red-darken-3">
                        Slobodna mesta: {{ t.left }}
                    </v-card-text>

                    <v-btn
                        color="red-darken-3"
                        variant="outlined"
                        @click="tours.addTour(t)"
                    >
                        Dodaj
                    </v-btn>
                    </v-card-actions>
                </v-col>

                <!-- TO -->
                <v-col cols="12" md="3">
                    <v-sheet 
                        class="pa-4 text-center h-100 d-flex flex-column align-center justify-center rounded-lg" 
                        color="grey-lighten-3"
                        elevation="9"
                    >
                        <v-card-title>{{ t.to }}</v-card-title>
                        <v-card-subtitle>{{ t.date }}</v-card-subtitle>
                        <v-card-title>{{ t.arrival }}</v-card-title>
                    </v-sheet>
                </v-col>

                </v-row>
            </v-card>
            </v-col>

        </v-row>
    </v-container>

    <BookingPanel />

    </template>

    <!--

    <v-bottom-sheet v-if="!mdAndUp"
        v-model="tours.active"
        height="100%"
        class="pa-6 w-100 "
        color="indigo-darken-4"
    >   
        <v-card class="pa-3 w-100 d-md-flex justify-space-evenly" elevation="9" color="indigo-darken-4">
            <v-card-title class="d-flex flex-column justify-space-evenly align-center" 
                v-if="tours.bookedTours.length > 0"
            >
                <h3>Dodato:</h3>
                <p>Ukupan Iznos: {{ tours.totalPrice }} EUR</p>
                <v-btn
                    @click="tours.removeAll"
                    variant="elevated"
                    min-width="120"
                    color="indigo-lighten-4"
                >
                    Isprazni
                </v-btn>
            </v-card-title>
            
            <v-card
                v-for="b in tours.bookedTours"
                class="pa-3" elevation="3"
                color="indigo-darken-2"
            >
                <input type="hidden" :value="b.tour_id">
                <v-card-title> {{ b.from }} - {{ b.to }} </v-card-title>
                <v-card-subtitle> {{ b.date }} </v-card-subtitle>
                <v-card-subtitle>Broj mesta: {{ b.places }} </v-card-subtitle>
                <v-card-title> {{ b.price }} EUR</v-card-title>
                <v-divider></v-divider>
                <v-card-actions class="d-flex justify-center">
                   <v-btn
                        icon="mdi-delete"
                        variant="plain"
                        color="red"
                        @click="tours.removeTour(b.tour_id)"
                    >
                    </v-btn> 
                </v-card-actions>
                
            </v-card>
            <v-sheet v-if="tours.bookedTours.length === 0" class="d-flex justify-center align-center w-100">
                <v-card-title >
                    Nemate izabranih vožnji
                </v-card-title>
            </v-sheet>
            <v-card-actions class="d-flex flex-column justify-space-evenly">
                <v-btn 
                    v-if="tours.bookedTours.length > 0"
                    @click="tours.book"
                    variant="elevated"
                    min-width="120"
                    color="red-darken-4"
                >
                    Rezerviši
                </v-btn>
                <v-btn 
                    @click="tours.active = false"
                    variant="elevated"
                    min-width="120"
                    color="indigo-darken-3"
                >
                    Zatvori
                </v-btn>
            </v-card-actions>
        </v-card>   
    </v-bottom-sheet>

    <v-bottom-navigation v-else
        :active="tours.active"
        height="300"
        class="pa-6 w-100 "
        color="indigo-darken-4"
    >   
        <v-card class="pa-3 w-100 d-md-flex justify-space-evenly" elevation="9" color="indigo-darken-4">
            <v-card-title class="d-flex flex-column justify-space-evenly align-center" 
                v-if="tours.bookedTours.length > 0"
            >
                <h3>Dodato:</h3>
                <p>Ukupan Iznos: {{ tours.totalPrice }} EUR</p>
                <v-btn
                    @click="tours.removeAll"
                    variant="elevated"
                    min-width="120"
                    color="indigo-lighten-4"
                >
                    Isprazni
                </v-btn>
            </v-card-title>
            
            <v-card
                v-for="b in tours.bookedTours"
                class="pa-3" elevation="3"
                color="indigo-darken-2"
            >
                <input type="hidden" :value="b.tour_id">
                <v-card-title> {{ b.from }} - {{ b.to }} </v-card-title>
                <v-card-subtitle> {{ b.date }} </v-card-subtitle>
                <v-card-subtitle>Broj mesta: {{ b.places }} </v-card-subtitle>
                <v-card-title> {{ b.price }} EUR</v-card-title>
                <v-divider></v-divider>
                <v-card-actions class="d-flex justify-center">
                   <v-btn
                        icon="mdi-delete"
                        variant="plain"
                        color="red"
                        @click="tours.removeTour(b.tour_id)"
                    >
                    </v-btn> 
                </v-card-actions>
                
            </v-card>
            <v-sheet v-if="tours.bookedTours.length === 0" class="d-flex justify-center align-center w-100">
                <v-card-title >
                    Nemate izabranih vožnji
                </v-card-title>
            </v-sheet>
            <v-card-actions class="d-flex flex-column justify-space-evenly">
                <v-btn 
                    v-if="tours.bookedTours.length > 0"
                    @click="tours.book"
                    variant="elevated"
                    min-width="120"
                    color="red-darken-4"
                >
                    Rezerviši
                </v-btn>
                <v-btn 
                    @click="tours.active = false"
                    variant="elevated"
                    min-width="120"
                    color="indigo-darken-3"
                >
                    Zatvori
                </v-btn>
            </v-card-actions>
        </v-card>   
    </v-bottom-navigation>

    <v-fade-transition mode="out-in">
        <RouterView />
    </v-fade-transition>
</template>



<template>
    <v-container class="text-center">
        <h1>Dostupne vožnje</h1>
    </v-container>
    
    <v-btn 
        class="ma-1"
        @click="tours.active = true"
    >
        Otvori
    </v-btn>

    <v-container class="pa-3 d-flex flex-column ga-6 align-center h-90">
        
        <v-card v-for="t in tours.available"
            class="d-flex space-between" 
            min-width="75%" 
            min-height="25%"
            elevation="2"
        >
        <v-row class="d-xs-flex flex-xs-column">
            <v-col
                cols="12"
                sm="4"
                lg="3"
                class="h-100 w-100"
                
            >
                <v-sheet
                    class="pa-4 d-flex justify-center align-center h-100 w-100 rounded"
                    color="grey-lighten-3"
                >
                    <v-sheet
                        class="h-100 w-100 d-flex flex-column justify-center align-center rounded-lg"
                        elevation="12"
                    >
                        <v-card-title> {{ t.from }} </v-card-title>
                        <v-card-subtitle> {{ t.date }}</v-card-subtitle>
                        <v-card-title> {{ t.departure }} </v-card-title>
                    </v-sheet>
                </v-sheet>
            </v-col>
            <v-col cols="12" sm="4" lg="6" class=" d-flex justify-center align-center">
                <v-card-actions class="d-flex flex-column justify-around">
                    <v-card-title> {{ t.priceTotal }} EUR</v-card-title>
                    <v-card-subtitle>Broj mesta</v-card-subtitle>
                    <v-number-input 
                        v-model="t.seats"
                        control-variant="split" 
                        placeholder="1"
                        :max="t.left"
                        :min="1"

                        v-on:update:model-value="tours.countSeats(t.id)"
                    ></v-number-input>
                    <v-card-text 
                        
                        color="red-darken-3"
                    >Trenutno: {{ t.left }} slobodnih mesta</v-card-text>
                    <v-btn
                        color="red-darken-3"
                        variant="outlined"
                        width="9rem"
                        @click="tours.addTour(t)"
                    >Dodaj</v-btn>
                </v-card-actions>
            </v-col>
            <v-col
                cols="12"
                sm="4"
                lg="3"
                class="h-100 w-100"
            >
                <v-sheet
                    class="pa-4 d-flex justify-center align-center h-100 w-100 rounded"
                    color="grey-lighten-3"
                    
                >
                    <v-sheet
                        rounded="2rem"
                        class="h-100 w-100 d-flex flex-column justify-center align-center rounded-lg"
                        elevation="12"
                    >
                        <v-card-title> {{ t.to}} </v-card-title>
                        <v-card-subtitle> {{ t.date }} </v-card-subtitle>
                        <v-card-title> {{ t.arrival }} </v-card-title>
                    </v-sheet>
                </v-sheet>
            </v-col>
        </v-row>
        </v-card>
        
        
    </v-container>
        <v-bottom-navigation
            :active="tours.active"
            height="300"
            class="pa-6 w-100"
            color="indigo-darken-4"
        >   
            <v-card class="pa-3 w-100 d-flex justify-space-evenly" elevation="9" color="indigo-darken-4">
                <v-card-title class="d-flex flex-column justify-space-evenly align-center" 
                    v-if="tours.bookedTours.length > 0"
                >
                    <h3>Dodato:</h3>
                    <p>Ukupan Iznos: {{ tours.totalPrice }} EUR</p>
                    <v-btn
                        @click="tours.removeAll"
                        variant="elevated"
                        min-width="120"
                        color="indigo-lighten-4"
                    >
                        Isprazni
                    </v-btn>
                </v-card-title>
                
                <v-card
                    v-for="b in tours.bookedTours"
                    class="pa-3" elevation="3"
                    color="indigo-darken-2"
                >
                    <input type="hidden" :value="b.tour_id">
                    <v-card-title> {{ b.from }} - {{ b.to }} </v-card-title>
                    <v-card-subtitle> {{ b.date }} </v-card-subtitle>
                    <v-card-subtitle>Broj mesta: {{ b.places }} </v-card-subtitle>
                    <v-card-title> {{ b.price }} EUR</v-card-title>
                    <v-divider></v-divider>
                    <v-card-actions class="d-flex justify-center">
                       <v-btn
                            icon="mdi-delete"
                            variant="plain"
                            color="red"
                            @click="tours.removeTour(b.tour_id)"
                        >
                        </v-btn> 
                    </v-card-actions>
                    
                </v-card>
                <v-sheet v-if="tours.bookedTours.length === 0" class="d-flex justify-center align-center w-100">
                    <v-card-title >
                        Nemate izabranih vožnji
                    </v-card-title>
                </v-sheet>
                <v-card-actions class="d-flex flex-column justify-space-evenly">
                    <v-btn 
                        v-if="tours.bookedTours.length > 0"
                        @click="tours.book"
                        variant="elevated"
                        min-width="120"
                        color="red-darken-4"
                    >
                        Rezerviši
                    </v-btn>
                    <v-btn 
                        @click="tours.active = false"
                        variant="elevated"
                        min-width="120"
                        color="indigo-darken-3"
                    >
                        Zatvori
                    </v-btn>
                </v-card-actions>
            </v-card>   
        </v-bottom-navigation>
    <v-fade-transition mode="out-in">
        <RouterView />
    </v-fade-transition>
</template>

-->