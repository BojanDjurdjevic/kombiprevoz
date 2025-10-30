<script setup>
    import { ref, onMounted } from 'vue';
    import { useDestStore } from '@/stores/destinations';
    const dest = useDestStore()

    onMounted(() => {
        dest.actions.fetchCities()
    })
</script>
<template>
    <v-container class="text-center">
       <h1>Hi from {{ dest.country }} </h1> 
    </v-container>
    <v-container>
        <v-row class="ma-9 w-100 pa-6">
            <v-col
                v-for="n in dest.cities"
                :key="n"
                cols="12"
                md="6"
                lg="3"
            >
                <v-card height="18rem" width="18rem" elevation="9" to="grad" @click="dest.takeCity(n)">
                    <v-img
                        class="align-center text-white"
                        height="100%"
                        :src="dest.getCityPrimaryImage(n)"
                        cover
                    >
                        <v-card-title class="text-center"> {{ n.name }} </v-card-title>
                        <!--
                        <v-card-actions class="d-flex justify-center w-100">
                            <v-btn color="white" variant="outlined" width="75%">Polasci</v-btn>
                        </v-card-actions>
                        -->
                    </v-img> 
                </v-card>
            </v-col>
            
        </v-row>
    </v-container>
    
</template>