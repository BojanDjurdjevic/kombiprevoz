<script setup>
import { useDestStore } from '@/stores/destinations';
import { useSearchStore } from '@/stores/search';
import { VDateInput } from 'vuetify/labs/VDateInput'
import { VNumberInput } from 'vuetify/labs/VNumberInput'
/*
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
*/
const dest = useDestStore()
const search = useSearchStore()

</script>

<template>
    <v-container fluid class="py-6">

    <!-- Breadcrumbs -->
    <v-breadcrumbs class="mb-4">
      <v-breadcrumbs-item to="/">Početna</v-breadcrumbs-item>
      <v-breadcrumbs-item to="/destinacije">Destinacije</v-breadcrumbs-item>
      <v-breadcrumbs-item to="/gradovi">{{ dest.country }}</v-breadcrumbs-item>
      <v-breadcrumbs-item>{{ dest.city }}</v-breadcrumbs-item>
    </v-breadcrumbs>

    <!-- Title -->
    <v-row justify="center" class="mb-4">
      <v-col cols="12" class="text-center">
        <h1>{{ dest.city }}</h1>
      </v-col>
    </v-row>

    <!-- Carousel -->
    <v-row justify="center" class="mb-6">
      <v-col cols="12" md="10">
        <v-carousel
          show-arrows-on-hover
          height="300"
          hide-delimiter-background
        >
          <v-carousel-item
            v-for="(item, i) in dest.cityPics"
            :key="i"
            :src="item"
            cover
            :alt="`Slika grada ${dest.city} - ${i+1}`"
            lazy-src="https://cdn.vuetifyjs.com/images/cards/docks.jpg"
          />
        </v-carousel>
      </v-col>
    </v-row>

    <!-- Reservation Form -->
    <v-row justify="center">
      <v-col cols="12" md="8">
        <h2 class="text-center mb-4">Rezerviši kombi transfer</h2>
        <v-sheet color="indigo-darken-2" class="pa-4 rounded-xl" elevation="2">

          <!-- Bound -->
          <v-row class="mb-4">
            <v-col cols="12">
              <v-radio-group inline v-model="search.bound">
                <v-radio label="Polazak" value="departure"></v-radio>
                <v-radio label="Sa Povratkom" value="allbound"></v-radio>
              </v-radio-group>
            </v-col>
          </v-row>

          <!-- Country & City Selection -->
          <v-row class="mb-4" dense>
            <v-col cols="12" sm="6">
              <v-autocomplete
                label="Polazna država"
                :items="dest.destinations"
                v-model="search.countryFrom"
                placeholder="Srbija"
                disabled
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6">
              <v-autocomplete
                label="Destinacija"
                :items="dest.destinations"
                v-model="search.countryTo"
                placeholder=" {{ dest.country }} "
                disabled
              ></v-autocomplete>
            </v-col>
          </v-row>

          <v-row class="mb-4" dense>
            <v-col cols="12" sm="6">
              <v-autocomplete
                label="Polazni grad"
                :items="dest.cities[search.countryFrom]"
                v-model="search.cityFrom"
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6">
              <v-autocomplete
                label="Destinacija"
                :items="dest.cities[search.countryTo]"
                v-model="search.cityTo"
                placeholder=" {{ dest.city }} "
                disabled
              ></v-autocomplete>
            </v-col>
          </v-row>

          <!-- Date & Seats -->
          <v-row class="mb-4" dense>
            <v-col cols="12" sm="6">
              <v-date-input v-model="search.outDate" label="Datum polaska"></v-date-input>
            </v-col>
            <v-col cols="12" sm="6" v-if="search.bound === 'allbound'">
              <v-date-input v-model="search.inDate" label="Datum povratka"></v-date-input>
            </v-col>
          </v-row>

          <v-row class="mb-4" dense>
            <v-col cols="12">
              <v-number-input
                v-model="search.seats"
                label="Broj mesta"
                :min="1"
                :max="7"
                control-variant="split"
              ></v-number-input>
            </v-col>
          </v-row>

          <!-- Submit Button -->
          <v-row justify="center">
            <v-btn color="pink-darken-2" large @click="search.sendSearch">
              Traži
            </v-btn>
          </v-row>

        </v-sheet>
      </v-col>
    </v-row>
  </v-container>

    <!--
    <v-container class="text-center">
        <h1> {{ dest.city }} </h1>
    </v-container>
    <v-container>
        <v-carousel :show-arrows="hover">
            <v-carousel-item
            v-for="(item,i) in dest.cityPics"
            :key="i"
            :src="item"
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
    -->
</template>

<style scoped>
.v-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.v-card:hover {
  transform: translateY(-4px);
  cursor: pointer;
}
</style>