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
        
        <v-row class="ma-9 w-100 pa-6">
            <v-col
                v-for="n in dest.destinations"
                :key="n"
                cols="12"
                md="6"
                lg="3"
            >
                <v-card height="18rem" width="18rem" elevation="9" to="gradovi" 
                    class="rounded-xl"
                    position="relative"
                    @click="dest.takeCountry(n.country)"
                >
                    <v-img
                        class="align-end text-white"
                        height="100%"
                        
                        cover
                        src="https://cdn.vuetifyjs.com/images/cards/docks.jpg"
                    >
                        <template #sources v-if="!n.file_path || n.file_path == ''">
                            <source srcset="https://cdn.vuetifyjs.com/images/cards/docks.jpg">
                        </template>
                        <template #sources v-if="n.file_path || n.file_path != ''">
                            <source :srcset="local + n.file_path">
                        </template>
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