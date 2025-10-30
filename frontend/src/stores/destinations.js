import { ref, computed, shallowRef } from 'vue'
import { defineStore } from 'pinia'
import { useSearchStore } from './search'
import router from '@/router'
import api from '@/api'

export const useDestStore = defineStore('dest', () => {
    const search = useSearchStore() /*
    const destinations = [ 'Srbija', 'Hrvatska', 'Slovenija', 'Nemačka', 'Austrija' ]
    const cities = {
        'Srbija': ['Beograd', 'Novi Sad', 'Niš'],
        'Hrvatska': ['Zagreb', 'Rijeka', 'Split'],
        'Slovenija': ['Ljubljana', 'Koper', 'Maribor']
    } */

    const destinations = ref(null)
    const cities = ref(null)
    
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

    const actions = ref({
        fetchCountries: async () => {
          const dto = {
            country: {
              id: "",
              name: ""
            }
          }
          try {
            const res = await api.getCountries(dto)
            //let input = Object.values(msg.data.drzave)
            destinations.value = res.data.drzave
            console.log(destinations.value) 
          } catch (error) {
            console.log(error)
          }
        },
    })

    return {
        cities, country, city, destinations, actions,
        takeCountry, takeCity,
    }
})