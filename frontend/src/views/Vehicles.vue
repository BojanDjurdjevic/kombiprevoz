<script setup>
    const vehicles = [] 

</script>

<template>
    <v-container fluid class="p-6">
      <v-row justify="center" class="mb-6" v-if="dest.city">
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
    </v-container>
</template>