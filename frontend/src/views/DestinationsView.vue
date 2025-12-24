<script setup>
    import { ref, onMounted } from 'vue'
    import { useSearchStore } from '@/stores/search';
    import { useDestStore } from '@/stores/destinations';
    const search = useSearchStore()
    const dest = useDestStore()

    const ass = "../assets/img/"
    const extension = '.png'
    const local = "https://localhost:8080/"

    // https://cdn.vuetifyjs.com/images/cards/docks.jpg

    onMounted(() => {
        dest.actions.fetchCountries()
    })
</script>

<template>
    <v-container >
        <v-row>
            <v-col  class="text-center">
               <h1 >Destinacije</h1>  
               <v-divider></v-divider>
            </v-col>
            
        </v-row>
        <v-row>
            <v-col class="text-center">
                <h2 class="subtitle">Sve destinacije kombi transfera</h2>
            </v-col>
        </v-row>
        <v-row class="ma-sm-9 w-100 pa-sm-6 d-flex justify-center">
            <v-col
                v-for="n in dest.destinations"
                :key="n"
                cols="12"
                sm="6"
                md="4"
                lg="3"
                class="d-flex justify-center mb-6"
            >
                <v-card elevation="9" to="gradovi" 
                    class="rounded-xl"
                    style="width: 100%; max-width: 280px; cursor: pointer;"
                    @click="dest.takeCountry(n)"
                >
                    <v-img
                        class="align-end text-white rounded-xl"
                        aspect-ratio="1.2"
                        lazy-src="https://cdn.vuetifyjs.com/images/cards/docks.jpg"
                        cover
                        alt="Kombi prevoz putnika do {{ n.name }} iz Srbije"
                        :src="dest.getCountryImage(n)"
                    >
                        <v-card-title class="text-center text-white"> {{ n.name }} </v-card-title>
                    </v-img> 
                </v-card>
            </v-col>
            
        </v-row>
        <v-row class="d-flex justify-center ">
            <section class="destinations-cta text-center mt-6 text-center ma-sm-6">
                <p>
                    Izaberite destinaciju i rezervišite kombi transfer direktno sa vaše adrese. Brzo, sigurno i udobno.
                </p>
            </section>
        </v-row>
    </v-container>
</template>

<style scoped>
.hover-card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hover-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 25px rgba(0,0,0,0.25);
}

/* Responsive spacing tweaks */
@media (max-width: 960px) {
  .v-row {
    margin-left: 0 !important;
    margin-right: 0 !important;
  }
}

.v-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.v-card:hover {
  transform: translateY(-4px);
  cursor: pointer;
}
</style>