<script setup>
    import { ref } from 'vue'
    import { useTourStore } from '@/stores/tours';
    import { useUserStore } from '@/stores/user';
    import { VNumberInput } from 'vuetify/labs/VNumberInput'
    import router from '@/router';

    const user = useUserStore()
    const tours = useTourStore()

    /*
    const active = ref(false)

    function addTour(t) {
        active.value = true
        
        const added = {
            id: t.id,
            from: t.from,
            to: t.to,
            date: t.date,
            time: t.departure,
            price: t.priceTotal,
            seats: t.seats,
            addressFrom: user.user.address,
            addressTo: ''
        }
        if(tours.bookedTours.length > 0) {
            let sum = 0
            tours.bookedTours.forEach(item => {
                if(item.id == added.id) {
                    sum++
                }
            })
            if(sum === 0) {
                tours.bookedTours.push(added)
            } else {
                alert(`Već ste dodali ovu vožnju u korpu`)
                active.value = false
            }
            
        } else {
            tours.bookedTours.push(added)
        }
        tours.calculateTotal()
    }
    function removeTour(id) {
        tours.bookedTours.forEach(t => {
            if(t.id == id) {
                tours.bookedTours.splice(tours.bookedTours.indexOf(t), 1)  
            }
        })
        tours.calculateTotal()
    }
    function removeAll() {
        while (tours.bookedTours.length > 0) {
            tours.bookedTours.shift()
        }
        tours.totalPrice = 0
    }

    function countSeats(id) {
        tours.available.forEach(item => {
            if(item.id == id) {
                item.priceTotal = item.price * item.seats
            }
        })
    }

    function book() {
        active.value = false
        router.push({
            name: 'korpa'
        })
    }
    */
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
                        :max="7"
                        :min="1"

                        v-on:update:model-value="tours.countSeats(t.id)"
                    ></v-number-input>
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
                    <input type="hidden" :value="b.id">
                    <v-card-title> {{ b.from }} - {{ b.to }} </v-card-title>
                    <v-card-subtitle> {{ b.date }} </v-card-subtitle>
                    <v-card-subtitle>Broj mesta: {{ b.seats }} </v-card-subtitle>
                    <v-card-title> {{ b.price }} EUR</v-card-title>
                    <v-divider></v-divider>
                    <v-card-actions class="d-flex justify-center">
                       <v-btn
                            icon="mdi-delete"
                            variant="plain"
                            color="red"
                            @click="tours.removeTour(b.id)"
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
    </v-container>

    <v-fade-transition mode="out-in">
        <RouterView />
    </v-fade-transition>
</template>