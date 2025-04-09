import { ref, computed, shallowRef } from 'vue'
import { defineStore } from 'pinia'
import { useSearchStore } from './search'
import router from '@/router'

export const useDestStore = defineStore('dest', () => {
    const search = useSearchStore()
    const destinations = [ 'Srbija', 'Hrvatska', 'Slovenija', 'Nemačka', 'Austrija' ]
    const cities = {
        'Srbija': ['Beograd', 'Novi Sad', 'Niš'],
        'Hrvatska': ['Zagreb', 'Rijeka', 'Split'],
        'Slovenija': ['Ljubljana', 'Koper', 'Maribor']
    }
    const country = ref('')
    const city = ref('')

    function takeCountry(name) {
        country.value = name
        search.countryFrom = 'Srbija'
        search.countryTo = name
    } 
    function takeCity(name) {
        city.value = name
        search.cityTo = name
    } 

    return {
        cities, country, city, destinations,
        takeCountry, takeCity,
    }
})