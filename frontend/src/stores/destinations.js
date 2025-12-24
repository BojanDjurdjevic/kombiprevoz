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
    const selectedCountryID = ref(null)
    const city = ref('')
    const cityPics = ref([])

    function takeCountry(n) {
        localStorage.setItem('country', JSON.stringify({id: n.id, name: n.name}))
        country.value = n.name
        selectedCountryID.value = n.id
        search.countryFrom = 'Srbija'
        search.countryTo = n.name
    } 
    function pushToLocale(n) {
      localStorage.setItem('city', JSON.stringify({id: n.id, name: n.name, pictures: n.pictures}))
    } 
    function takeCity(n) {
        localStorage.setItem('city', JSON.stringify({id: n.id, name: n.name, pictures: n.pictures}))
        console.log(n)
        city.value = n.name
        search.cityTo = n.name
        console.log(n.pictures)
        cityPics.value = getCityImages(n.pictures || [])
        console.log(cityPics.value)
    } 

    function adminCountryImage(n) {
      if (!n.file_path || n.file_path === '') {
        return null
      }
      return 'http://localhost:8080/' + n.file_path
    }

    function getCountryImage(n) {
      if (!n.file_path || n.file_path === '') {
        return 'https://cdn.vuetifyjs.com/images/cards/docks.jpg'
      }
      //console.log('http://localhost:8080/' + n.file_path)
      return 'http://localhost:8080/' + n.file_path
    }
    function getCityPrimaryImage(n) {
      if (n.pictures.length < 1) {
        return 'https://cdn.vuetifyjs.com/images/cards/docks.jpg'
      }
      
      console.log('http://localhost:8080/' + n.pictures[0].file_path)
      return 'http://localhost:8080/' + n.pictures[0].file_path
    }
    function getCityImages(pictures) {
      let arr = []
      if (pictures.length < 1) {
        arr.push('https://cdn.vuetifyjs.com/images/cards/docks.jpg')
        return arr
      }
      
      pictures.forEach(pic => {
        arr.push(getCountryImage(pic))
      });
      console.log(arr)
      return arr
    }

    const storedTown = ref({})

    function hydrateFromStorage() {
      const storedCountry = JSON.parse(localStorage.getItem('country'))
      const storedCity = JSON.parse(localStorage.getItem('city'))

      if (storedCountry) {
        country.value = storedCountry.name
        selectedCountryID.value = storedCountry.id
      }

      if (storedCity) {
        city.value = storedCity.name
        cityPics.value = getCityImages(storedCity.pictures || [])
        search.cityTo = storedCity.name
        storedTown.value = storedCity
      }
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
        fetchCities: async () => {
          const dto = {
              byID: selectedCountryID.value
          }

          try {
              const res = await api.getCities(dto)
              cities.value = res.data.cities
              console.log(cities.value)
              console.log(res.data)
          } catch (error) {
              console.log(error)
          }
        }
    })

    return {
        cities, country, city, destinations, actions, selectedCountryID, cityPics,
        storedTown,

        takeCountry, takeCity, getCountryImage, getCityPrimaryImage, getCityImages,
        adminCountryImage, pushToLocale, hydrateFromStorage,
    }
})