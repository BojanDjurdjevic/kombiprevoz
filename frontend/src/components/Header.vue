<script setup>
import { ref, shallowRef } from 'vue';
import Search from './Search.vue';
import MyAvatar from './MyAvatar.vue';
import { useSearchStore } from '@/stores/search';
import { useTourStore } from '@/stores/tours';
import { useTheme } from 'vuetify'

const active = ref(false)
const search = useSearchStore()
const tours = useTourStore()

const darkTheme = ref(true)
const theme = useTheme()

function changeTheme() {
  darkTheme.value = !darkTheme.value

  theme.global.name.value = darkTheme.value ? 'dark' : 'light'
}

if(localStorage.getItem('myCart')) {
  onload = () => {
    tours.available = JSON.parse(localStorage.getItem('avTours'))
    tours.bookedTours = JSON.parse(localStorage.getItem('myCart'))
    tours.calculateTotal()
  }
}


</script>

<template>
      <v-toolbar class="text-white" color="indigo-darken-4" >

        <v-menu >
          <template v-slot:activator="{ props }">
            <v-btn icon="mdi-menu" class="d-block d-md-none" v-bind="props"></v-btn>
          </template>

          <v-list height="90vh" width="95vw" class="d-flex flex-column align-center justify-space-evenly pa-2">
            <v-list-item>
              <v-list-item-title ><v-btn variant="text" to="/">Početna</v-btn></v-list-item-title>
            </v-list-item>
            <v-list-item>
              <v-list-item-title ><v-btn variant="text" to="/rezervacije">Moje Rezervacije</v-btn></v-list-item-title>
            </v-list-item>
            <v-list-item>
              <v-list-item-title ><v-btn variant="text" to="/destinacije">Destinacije</v-btn></v-list-item-title>
            </v-list-item>
            <v-list-item>
              <v-list-item-title ><v-btn variant="text" to="/kontakt">Kontakt</v-btn></v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
        
  
        <v-toolbar-title >KombiPrevoz</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-dialog
          v-model="search.dialog"
        >
          <template v-slot:activator="{ props: activatorProps }" >
            <v-btn 
              variant="plain"
              v-bind="activatorProps"
              class="d-none d-md-block"
              @click="search.allCountries(search.allCount)"
              >Pretraga
            </v-btn>
            <v-btn 
              icon="mdi-magnify"
              variant="plain"
              v-bind="activatorProps"
              class="d-block d-md-none"
              >
            </v-btn>
          </template>
          <Search />
        </v-dialog>
        
        <v-row class="d-none d-md-block">
          <v-btn variant="plain" to="/">Početna</v-btn>
          <v-btn variant="plain" to="/rezervacije">Moje Rezervacije</v-btn>
          <v-btn variant="plain" to="/destinacije">Destinacije</v-btn>
          <v-btn variant="plain" to="/kontakt">Kontakt</v-btn>
        </v-row>

        <v-spacer></v-spacer>

        <v-btn
          @click="changeTheme"
        >
          <v-icon
            :icon="darkTheme ? 'mdi-weather-night' : 'mdi-weather-sunny'"
          ></v-icon>
        </v-btn>

        <v-btn
          icon
          to="/korpa"
        >
          <v-badge color="error" :content="tours.bookedTours.length" v-if="tours.bookedTours.length > 0">
            <v-icon icon="mdi-van-passenger"></v-icon>
          </v-badge>
          <v-icon icon="mdi-van-passenger" v-if="tours.bookedTours.length < 1"></v-icon>
        </v-btn>

        <v-spacer class="d-block d-sm-none"></v-spacer>

        <MyAvatar />

      </v-toolbar>
</template>