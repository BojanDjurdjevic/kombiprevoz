<script setup>
import { RouterLink, RouterView } from 'vue-router'
import Header from './components/Header.vue';
import { useUserStore } from './stores/user';
import { VOverlay, VProgressCircular } from 'vuetify/components'
import { useRoute } from 'vue-router';
import Footer from './components/Footer.vue';

const user = useUserStore()
const route = useRoute()
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
        <v-overlay
          :model-value="!!user.successMsg || !!user.errorMsg"
          class="d-flex align-start justify-center"
          z-index="9999"
        >
          <v-alert
            v-if="user.successMsg"
            type="success"
            title="Pozdrav!"
            :text="user.successMsg"
            elevation="12"
            class="alert-full"
            @click="user.successMsg = false"
          />

          <v-alert
            v-if="user.errorMsg"
            type="error"
            title="Greška!"
            :text="user.errorMsg"
            elevation="12"
            class="alert-full"
            @click="user.errorMsg = false"
          />
        </v-overlay>

      </v-fade-transition>
      
      <v-fade-transition mode="out-in">
        <RouterView />
      </v-fade-transition>
    </v-main>
    <Footer v-if="!route.path.startsWith('/admin')" />
  </v-app>
</template>

<style >
  * {
    font-family: Verdana, Geneva, Tahoma, sans-serif;
  }

  .alert-full {
    width: 90vw;       
    max-width: 100%;
    border-radius: 0; 
    margin-top: 7vh;
  }

</style>
