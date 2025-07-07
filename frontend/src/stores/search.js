import { ref, computed, shallowRef } from 'vue'
import { defineStore } from 'pinia'
import { useTourStore } from './tours'
import router from '@/router'
import api from '@/api'

export const useSearchStore = defineStore('search', () => {
  const tours = useTourStore()

  const destinations = [
    {country: 'Srbija'}, {country: 'Hrvatska'}, {country: 'Slovenija'}, {country: 'Nemačka'}, {country: 'Austrija'}
  ]
  const dialog = shallowRef(false)

  const bound = ref('departure')

  const countryFrom = ref('')
  const countryTo = ref('')

  const cityFrom = ref('')
  const cityTo = ref('')

  const outDate = ref(null)
  const inDate = ref(null)
  const seats = ref(1)

  function reverseCountries() {
    if(countryFrom.value != '' && cityFrom.value != '') {
      const temp1 = countryFrom.value
      const temp2 = countryTo.value
      countryFrom.value = temp2
      countryTo.value = temp1
      //reverseCities
      if(cityFrom.value != '' && cityTo.value != '') {
        const temp3 = cityFrom.value
        const temp4 = cityTo.value
        cityFrom.value = temp4
        cityTo.value = temp3
      }
    }
    
      
  } 

  function sendSearch() {
    dialog.value = false
    let dto = {
      'countryFrom' : countryFrom.value,
      'countryTo' : countryTo.value,
      'cityFrom' : cityFrom.value,
      'cityTo' : cityTo.value,
      'outbound': outDate.value,
      'inbound': inDate.value,
      'seats': seats.value
    }
    console.log(dto)
    // mySearch: 
    tours.mySearch = dto
    //console.log(tours.mySearch)

    countryFrom.value = ''
    countryTo.value = ''
    cityFrom.value = ''
    cityTo.value = ''
    outDate.value = null
    inDate.value = null
    seats.value = 1
    router.push({
      name: 'rezultati'
    })
  }
  function cityRules(val) {
    if(val) {
      return true
    } else {
      return false
    }
  }
  /*
  const searchData = shallowRef({
    outDate: null,
    inDate: null,
    seats: 1
  })
  */ 
  const exCountry = { 
    "user": {
        "id": 10,
        "name": "Valentina",
        "email": "pininfarina164@gmail.com",
        "pass": "Ljubavsonmojija!369",
        "status": "Driver",
        "city": "Novi Sad",
        "address": "Seljačkih Buna 29",
        "phone": "062640227"
    },
    "country": {
      "country_id": ""
    } 
}
  async function allCountries() {
    try {
      const msg = await api.getCountries(exCountry)
      console.log(msg)
    } catch (error) {
      console.log(error)
    }
    
  }

  return { 
    dialog, bound, countryFrom, countryTo, cityFrom, cityTo,/* searchData, */ outDate, inDate, seats, destinations,
    exCountry,
    sendSearch, reverseCountries, cityRules, allCountries
  }

})
