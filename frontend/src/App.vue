<script setup>
import { RouterLink, RouterView } from 'vue-router'
import Header from './components/Header.vue';
import { useUserStore } from './stores/user';
import { VOverlay, VProgressCircular } from 'vuetify/components'

const user = useUserStore()
</script>

<template>
  <v-app>
    <v-overlay :model-value="user.loading"
      class="align-center justify-center"
      
    >
      <v-progress-circular
        v-if="user.loading"
        indeterminate
        color="indigo-darken-3"
        size="72"
      ></v-progress-circular>
    </v-overlay>
    <Header />
    <v-main>
      <v-fade-transition mode="out-in">
        <v-alert v-if="user.successMsg"
          :text="user.successMsg"
          title="Pozdrav!"
          type="success"
          class="position-sticky top-0 pa-3"
          elevation="12"
        ></v-alert>
      </v-fade-transition>
      <v-fade-transition mode="out-in">
        <v-alert v-if="user.errorMsg"
          :text="user.errorMsg"
          title="Greška!"
          type="error"
          class="position-sticky top-0 pa-3"
          elevation="12"
          z-index="6"
        ></v-alert>
      </v-fade-transition>
      <v-fade-transition mode="out-in">
        <RouterView />
      </v-fade-transition>
      <!--
      <router-view v-slot="{ HomeView, MyBookings, Destinations,
        CitiesView, CityBook, BookNow, SearchResult }">
        <transition >
          <component :is="SearchResult" />
        </transition>
      </router-view>
      -->
    </v-main>
  </v-app>
</template>

<style scoped>
  * {
    font-family: Verdana, Geneva, Tahoma, sans-serif;
  }
</style>
