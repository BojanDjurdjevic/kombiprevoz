<script setup>
import { useDestStore } from '@/stores/destinations';
import { useSearchStore } from '@/stores/search';
import { VDateInput } from 'vuetify/labs/VDateInput'
import { VNumberInput } from 'vuetify/labs/VNumberInput'
import { onMounted } from 'vue';
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
/*
onMounted(() => {
    let city = JSON.parse(localStorage.getItem('city'))
    console.log('Grad iz pogleda: ', city)
    dest.takeCity(city)
}) 
*/

onMounted(async () => {
  dest.hydrateFromStorage()

  if (!dest.destinations) {
    await dest.actions.fetchCountries()
  }

  if (!dest.cities && dest.selectedCountryID) {
    await dest.actions.fetchCities()
  }

  if(!search.countryFrom || !search.cityFrom) {
    search.countryTo = dest.storedCountry
    search.cityTo = dest.storedCity
    if(dest.country !== 'Srbija') {
      search.countryFrom = {name: 'Srbija', id: 1}
      search.afterCountryFrom(search.countryFrom, true)
      search.availableCountries = [{name: 'Srbija', id: 1}]
      dest.fillFromCities(search.cityTo)
    } else {
      search.countryFrom = ''
      search.allCountries(search.allCount)
      console.log('Ava Countries iz CityBook: ', search.availableCountries)
      /*
      const filtered = search.availableCountries.filter(c => c.name !== 'Srbija')
      search.availableCountries = filtered */
    }
  }
})

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
    <v-row justify="center" class="mb-4" v-if="dest.city">
      <v-col cols="12" class="text-center">
        <h1>{{ dest.city }}</h1>
      </v-col>
    </v-row>

    <!-- Carousel -->
    <v-row justify="center" class="mb-6" v-if="dest.city">
      <v-col cols="12" md="10">
        <v-carousel
            v-if="dest.cityPics.length"
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
    <v-row justify="center" v-if="dest.city">
      <v-col cols="12" md="8">
        <h2 class="text-center mb-4">Rezerviši kombi transfer</h2>
        <v-sheet color="indigo-darken-2" class="pa-4 rounded-xl" elevation="2">
          <v-alert v-if="search.violated"
            text="Molimo Vas da popunite sva polja!"
            title="Greška"
            type="error"
            @click="search.violated = false"
          ></v-alert>
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
                v-if="dest.cities && dest.cities.length"
                label="Polazna država"
                item-title="name"
                item-value="id"
                :items="search.availableCountries"
                v-model="search.countryFrom"
                
                v-on:update:model-value="val => search.afterCountryFrom(val, true)"
                return-object
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6">
              <v-autocomplete
                v-if="dest.cities && dest.cities.length"
                label="Destinacija"
                item-title="name"
                item-value="id"
                :items="search.availableCountriesTo"
                v-model="search.countryTo"
                disabled
                return-object
              ></v-autocomplete>
            </v-col>
          </v-row>

          <v-row class="mb-4" dense>
            <v-col cols="12" sm="6">
              <v-autocomplete
                v-if="dest.cities && dest.cities.length"
                label="Polazni grad"
                :items="search.availableCities"
                item-title="name"
                item-value="name"
                v-model="search.cityFrom"
                return-object
                v-on:update:model-value="search.dateQuery"
                :disabled="!search.countryFrom"
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6">
              <v-autocomplete
                v-if="dest.cities && dest.cities.length"
                label="Destinacija"
                item-title="name"
                item-value="name"
                v-model="search.cityTo"
                disabled
                return-object
              ></v-autocomplete>
            </v-col>
          </v-row>

          <!-- Date & Seats -->
          <v-row class="mb-4" dense>
            <v-col cols="12" sm="6">
              <v-date-input 
                :rules="[search.rules.required]"
                v-model="search.outDate"
                :label="search.allowedDays.allowed.length
                ? 'Datum Polaska' : 'Nema dostupnih datuma za ovu rutu, promenite grad / državu'" 
                :disabled="!search.availableCities.length || !search.cityFrom"
                :allowed-dates="search.isDateAllowed"
              >
                <template #day="{ date }">
                  <div
                    :class="[
                      'v-btn',
                      'v-size-default',
                      {
                        'red-darken-2 pointer-events-none' : search.allowedDays.fullyBooked.includes(date),
                        'opacity-50 pointer-events-none': !search.isDateAllowed(date)
                      }
                    ]"
                  >
                      {{ new Date(date).getDate() }}
                  </div>
                </template>
              </v-date-input>
            </v-col>
            <v-col cols="12" sm="6" v-if="search.bound === 'allbound'">
              <v-date-input  
                v-model="search.inDate"
                label="Datum Povratka" v-if="search.bound == 'allbound'"
                :allowed-dates="search.isDateInAllowed"
              >
                <template #day="{ date }">
                    <div
                      :class="[
                        'v-btn',
                        'v-size-default',
                        {
                          'bg-red-darken-2 text-white pointer-events-none' : search.allowedDaysIn.fullyBooked.includes(date),
                          'opacity-50 pointer-events-none': !search.isDateInAllowed(date)
                        }
                      ]"
                    >
                        {{ new Date(date).getDate() }}
                    </div>
                </template>
              </v-date-input>
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