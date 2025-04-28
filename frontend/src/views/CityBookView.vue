<script setup>
import { useDestStore } from '@/stores/destinations';
import { useSearchStore } from '@/stores/search';
import { VDateInput } from 'vuetify/labs/VDateInput'
import { VNumberInput } from 'vuetify/labs/VNumberInput'

const items = [
    {
      src: 'https://cdn.vuetifyjs.com/images/carousel/squirrel.jpg',
    },
    {
      src: 'https://cdn.vuetifyjs.com/images/carousel/sky.jpg',
    },
    {
      src: 'https://cdn.vuetifyjs.com/images/carousel/bird.jpg',
    },
    {
      src: 'https://cdn.vuetifyjs.com/images/carousel/planet.jpg',
    },
  ]

const dest = useDestStore()
const search = useSearchStore()

</script>

<template>
    <v-container class="text-center">
        <h1> {{ dest.city }} </h1>
    </v-container>
    <v-container>
        <v-carousel :show-arrows="hover">
            <v-carousel-item
            v-for="(item,i) in items"
            :key="i"
            :src="item.src"
            cover
            ></v-carousel-item>
        </v-carousel>
        
    </v-container>
    
    <v-container class="text-center">
        <h2>Rezerviši</h2>

        <v-sheet color="indigo-darken-2" class="pa-1" :elevation="2" >
            
            <v-container class="ma-1 pa-3 d-flex flex-column flex-md-row justify-space-evenly">
            <v-row class="d-flex flex-column justify-space-evenly ma-1 pa-2">
                <v-sheet color="indigo-darken-2" class="d-flex justify-space-evenly" width="90%">
                <v-radio-group inline v-model="search.bound">
                    <v-radio label="Polazak" value="departure"></v-radio>
                    <v-radio label="Sa Povratkom" value="allbound"></v-radio>
                </v-radio-group>
                </v-sheet>
            </v-row>
            </v-container>
            <v-container class="ma-1 pa-3 d-flex flex-column flex-md-row justify-space-evenly">
            <v-row class="d-flex flex-column justify-space-evenly ma-1 pa-2">
                <h4>Država</h4>
                <v-sheet color="indigo-darken-2" class="d-flex flex-column flex-md-row justify-space-evenly " width="90%" 
                >
                <v-autocomplete
                    clearable
                    label="From"
                    :items="dest.destinations"
                    v-model="search.countryFrom"
                    placeholder="Srbija"
                    disabled
                ></v-autocomplete>
                <v-spacer></v-spacer>
                
                <v-spacer></v-spacer>
                <v-autocomplete
                    clearable
                    label="To"
                    :items="dest.destinations"
                    v-model="search.countryTo"
                    :placeholder="dest.country"
                    disabled
                ></v-autocomplete>
                </v-sheet>
            </v-row>

            <v-row class="d-flex flex-column justify-space-evenly ma-1 pa-2">
                <h4>Grad</h4>
                <v-sheet color="indigo-darken-2" class="d-flex flex-column flex-md-row justify-space-evenly" width="90%"
                >
                <v-autocomplete
                    clearable
                    label="From"
                    :items="dest.cities[search.countryFrom]"
                    v-model="search.cityFrom"
                    
                ></v-autocomplete>
                <v-spacer></v-spacer>
                
                <v-spacer></v-spacer>
                <v-autocomplete
                    clearable
                    label="To"
                    :items="dest.cities[search.countryTo]"
                    v-model="search.cityTo"
                    :placeholder="dest.city"
                    disabled
                ></v-autocomplete>
                </v-sheet>
            </v-row>
            </v-container>

            <v-container class="ma-1 pa-3 d-flex flex-column flex-md-row justify-space-evenly">
            <v-row class="d-flex flex-column justify-space-evenly ma-1 pa-2">
                <h4>Polazak</h4>
                <v-sheet color="indigo-darken-2" class="d-flex justify-space-evenly" width="90%" >
                <v-date-input 
                    v-model="search.outDate"
                    label="Datum Polaska" 
                ></v-date-input>
                </v-sheet>
            </v-row>
            
            <v-row class="d-flex flex-column justify-space-evenly ma-1 pa-2" >
                <h4 v-if="search.bound == 'allbound'">Povratak</h4>
                <v-sheet color="indigo-darken-2" class="d-flex justify-space-evenly" width="90%" >
                <v-date-input  
                    v-model="search.inDate"
                    label="Datum Povratka" v-if="search.bound == 'allbound'"
                ></v-date-input>
                </v-sheet>
            </v-row>
            </v-container>

            <v-container class="ma-1 pa-3 d-flex justify-space-evenly">
            <v-row class="d-flex flex-column justify-space-evenly ma-1 pa-2">
                <h4>Broj mesta</h4>
                <v-sheet color="indigo-darken-2" class="d-flex justify-space-evenly" width="90%">
                <v-number-input 
                    v-model="search.seats"
                    control-variant="split" 
                    :max="7"
                    :min="1"
                ></v-number-input>
                </v-sheet>
            </v-row>
            
            <v-row class="d-flex flex-column justify-space-evenly ma-1 pa-2">
                <v-sheet color="indigo-darken-2" class="d-flex justify-space-evenly" width="90%">
                <v-btn variant="outlined" @click="search.sendSearch" >Traži</v-btn>
                </v-sheet>
            </v-row>
            </v-container>
        </v-sheet>
    </v-container>
</template>