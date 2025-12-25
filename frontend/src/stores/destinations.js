import { ref, computed, shallowRef } from 'vue'
import { defineStore } from 'pinia'
import { useSearchStore } from './search'
import router from '@/router'
import api from '@/api'

export const useDestStore = defineStore('dest', () => {
    const search = useSearchStore()

    const destinations = ref(null)
    const cities = ref(null)
    
    const country = ref('')
    const selectedCountryID = ref(null)
    const city = ref('')
    const cityPics = ref([])

    const tourCitiesFrom = ref([])
    async function fillFromCities(c) {
      if(!c) return
      console.log('poslani grad: ', c)
      const dto = {
        city: {
          from: false,
          name: c.name
        }
      }
    
      try {
        const res = await api.getTours(dto)
        tourCitiesFrom.value = res.data.success ? res.data.toCities : []
        if(res.data.has_cities) {
          console.log('Dostpni gradovi POLASKA: ', tourCitiesFrom.value)
          search.availableCities = search.availableCities.filter(c =>
            tourCitiesFrom.value.some(t => t.from_city === c.name)
          )
        } else {
          search.availableCities = []
        }
        console.log(search.availableCities)
      } catch (error) {
        console.log(error)
      }
    }

    function countryToLocale(n) {
      console.log('za lokal: ', {id: n.id, name: n.name})
      localStorage.setItem('country', JSON.stringify({id: n.id, name: n.name}))
      return n
    }

    async function takeCountry(n) {
        let mycountry = await countryToLocale(n)
        country.value = mycountry.name
        selectedCountryID.value = mycountry.id
        if(n.name !== 'Srbija') {
          search.countryFrom = {name: 'Srbija', id: 1}
          search.countryTo = n
        } else {
          search.countryTo = {name: 'Srbija', id: 1}
          search.countryFrom = ''
          search.allCountries(search.allCount)
        }
    } 

    function takeCity(n) {
        localStorage.setItem('city', JSON.stringify({id: n.id, name: n.name, pictures: n.pictures}))
        console.log(n)
        city.value = n.name
        search.cityTo = n
        console.log(search.cityTo)
        cityPics.value = getCityImages(n.pictures || [])
        search.afterCountryFrom(search.countryFrom, true)
        fillFromCities(search.cityTo)
        console.log('Obj za PRETRAGU: ', {
          countryFrom: search.countryFrom,
          countryTo: search.countryTo,
          cityTo: search.cityTo,
          cities: search.availableCities  
        })
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
          let id
          let cntry
          if(selectedCountryID.value) {
            id = selectedCountryID.value
          } else {
            cntry = JSON.parse(localStorage.getItem('country'))
            id = cntry.id
          }
          const dto = {
              byID: id
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
        adminCountryImage, hydrateFromStorage,
    }
})