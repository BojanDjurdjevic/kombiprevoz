<script setup>
    import { ref } from 'vue'
    import { VDateInput } from 'vuetify/labs/VDateInput'
    import { VNumberInput } from 'vuetify/labs/VNumberInput'
    import { useSearchStore } from '@/stores/search';

    const search = useSearchStore()
    const destinations = [ 'Srbija', 'Hrvatska', 'Slovenija', 'Nemačka', 'Austrija' ]
    const cities = {
      'Srbija': ['Beograd', 'Novi Sad', 'Niš'],
      'Hrvatska': ['Zagreb', 'Rijeka', 'Split'],
      'Slovenija': ['Ljubljana', 'Koper', 'Maribor']
    }
</script>

<template >
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
                width=""
                label="From"
                :items="destinations"
                v-model="search.countryFrom"
              ></v-autocomplete>
              <v-spacer></v-spacer>
              <v-btn icon="mdi-unfold-more-vertical" class="d-none d-md-block" @click="search.reverseCountries"></v-btn>
              <v-btn icon="mdi-unfold-more-horizontal" class="d-block d-md-none" @click="search.reverseCountries"></v-btn>
              <v-spacer></v-spacer>
              <v-autocomplete
                clearable
                label="To"
                :items="destinations"
                v-model="search.countryTo"
              ></v-autocomplete>
            </v-sheet>
          </v-row>

          <v-row class="d-flex flex-column justify-space-evenly ma-1 pa-2">
            <h4>Grad</h4>
            <v-sheet color="indigo-darken-2" class="d-flex flex-column flex-md-row justify-space-evenly" width="90%"
            >
              <v-autocomplete
                clearable
                width=""
                label="From"
                :items="cities[search.countryFrom]"
                v-model="search.cityFrom"
              ></v-autocomplete>
              <v-spacer></v-spacer>
              <v-btn icon="mdi-unfold-more-vertical" class="d-none d-md-block" @click="search.reverseCountries"></v-btn>
              <v-btn icon="mdi-unfold-more-horizontal" class="d-block d-md-none" @click="search.reverseCountries"></v-btn>
              <v-spacer></v-spacer>
              <v-autocomplete
                clearable
                label="To"
                :items="cities[search.countryTo]"
                v-model="search.cityTo"
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
              <v-fade-transition mode="out-in">
                <v-btn variant="outlined" @click="search.sendSearch" >Traži</v-btn>
              </v-fade-transition>
            </v-sheet>
          </v-row>
        </v-container>
    </v-sheet>
</template>