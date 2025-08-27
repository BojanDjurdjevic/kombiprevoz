import { ref, computed, shallowRef } from 'vue'
import { defineStore } from 'pinia'
import { useTourStore } from './tours'
import router from '@/router'
import api from '@/api'

export const useSearchStore = defineStore('search', () => {
  const tours = useTourStore()

  const rules = {
    required: (value) => !!value || "Obavezno polje",
    counter: (value) => value.length <= 21 || "Maksimum 21 karakter"   
  }

  const violated = ref(false)

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

  const allCount = {
    country: {
      id: "",
      name: ""
    }
  }

  function reverseCountries() {
    console.log(countryFrom.value.name + "\n" + countryTo.value.name)
    if(countryFrom.value != '' && countryTo.value != '') {
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

      allCities(countryFrom.value.id, true)
      allCities(countryTo.value.id, false)
      dateQuery()
    }
    console.log(countryFrom.value.name + "\n" + countryTo.value.name)
      
  } 

  function cityRules(val) {
    if(val) {
      return true
    } else {
      return false
    }
  }
  function dateFormat(date) {
    let d = new Date(date)
    let year = String(d.getFullYear()) 
    let months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']
    let m = d.getMonth()
    let month = months[m]
    let dates = String(d.getDate())
    let formated = year + "-" + month + "-" + dates

    return formated
  }
  function qDateForm() {
    let d = new Date()
    let year = String(d.getFullYear()) 
    let month = String(d.getMonth())
    let months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']
    let formD = year + "-" + months[month] + "%"

    return formD
  }
  const exCountry = { 
    country: {
      "country_id": 6,
      "country_name": "Belgija"
    } 
  }

  const availableCountries = ref([])
  const availableCountriesTo = ref([])

  const availableCities = ref([])
  const availableCitiesTo = ref([])

  async function allCountries(data) {
    if(data && data.id) {
      try {
        const msg = await api.getCountries(data.id)
        let input = Object.values(msg.data.drzave)
        availableCountries.value = input
        console.log(availableCountries.value) 
      } catch (error) {
        console.log(error)
      }
    } else {
      try {
        const msg = await api.getCountries(data)
        let input = Object.values(msg.data.drzave)
        availableCountries.value = input
        console.log(availableCountries.value) 
      } catch (error) {
        console.log(error)
      }
    }
    
    
  }

  function getCountryFrom(id) {
    availableCountriesTo.value = []
    availableCountries.value.forEach(item => {
      if(item.id != id) {
        availableCountriesTo.value.push(item)
      }
    });
  }

  async function allCities(id, from) {
    getCountryFrom(id)
    let dto = {
      country_id: id
    }
    try {
      const msg = await api.getCities(dto)
      if(from) {
        availableCities.value = Object.values(msg.data.cities)
      } else {
        availableCitiesTo.value = Object.values(msg.data.cities)
      }
      console.log(availableCities.value)
      console.log(availableCitiesTo.value)
    } catch (error) {
      console.log(error)
    }
  }

  // check the dates for Date Picker

  const allowedDays = ref({
    fullyBooked: [],
    fulls: [],
    allowed: []
  })
  const allowedDaysIn = ref({
    fullyBooked: [],
    fulls: [],
    allowed: []
  })

  async function dateQuery() {
    let formD = qDateForm()
    let dto = {
      days: {
        from: cityFrom.value.name,
        to: cityTo.value.name,
        format: formD
      }
    }
    console.log(dto)
    try {
      const msg = await api.checkAvailableDates(dto)
      allowedDays.value.fullyBooked = msg.data.fullyBooked
      allowedDays.value.allowed = msg.data.allowed

      allowedDaysIn.value.fullyBooked = msg.data.fullyBookedIn
      allowedDaysIn.value.allowed = msg.data.allowedIn
      console.log(dto.days.format)
      console.log(allowedDays.value.fullyBooked + "\n" + allowedDaysIn.value.fullyBooked)
    } catch (error) {
      console.log(error)
    }
    
  }
  
  const isDateAllowed = (dateStr) => {
    const date = new Date(dateStr)
    const dayOfWeek = date.getDay()

    let d = date
    let year = String(d.getFullYear()) 
    let months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']
    let m = d.getMonth()
    let month = months[m]
    let dates = String(d.getDate())
    let formated = year + "-" + month + "-" + dates

    return allowedDays.value.allowed.includes(dayOfWeek) && !allowedDays.value.fullyBooked.includes(formated)
  } 
  const isDateInAllowed = (dateStr) => {
    const date = new Date(dateStr)
    const dayOfWeek = date.getDay()
    let formated = dateFormat(dateStr)

    return allowedDaysIn.value.allowed.includes(dayOfWeek) && !allowedDaysIn.value.fullyBooked.includes(formated)
  } 

  async function sendSearch() {
    if(!cityFrom.value || !cityTo.value || !countryFrom.value || !countryTo.value || !outDate.value) {
      violated.value = true
      return 
    } else {
        violated.value = false
        dialog.value = false
        let formated = dateFormat(outDate.value) 
        let formatedIn 

        if(inDate.value != null) {
          formatedIn = dateFormat(inDate.value)
        } else {
          formatedIn = null
        }

        let dto = {
            search: {
              from: cityFrom.value.name,
              to: cityTo.value.name,
              date: formated,
              inbound: formatedIn,
              seats: seats.value
            }
        }
        console.log(dto)
        try {
          const msg = await api.getTours(dto)
          console.log(msg.data)
          tours.available = msg.data.tour 
          localStorage.setItem('avTours', JSON.stringify(tours.available))
        } catch (error) {
          console.log(error)
        } finally { 
          tours.mySearch = dto

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
    }
  }

  //--------------------- TESTING API
  async function newCountry() {
    try {
      const msg = await api.insertCountry(exCountry)
      console.log(msg.data)
    } catch (error) {
      console.log(error)
    }
  }
  async function changeCountry() {
    try {
      const msg = await api.updateCountry(exCountry)
      console.log(msg.data)
    } catch (error) {
      console.log(error)
    }
  }
  async function dropCountry() {
    try {
      const msg = await api.deleteCountry(exCountry)
      console.log(msg.data)
    } catch (error) {
      console.log(error)
    }
  }

  return { 
    dialog, bound, countryFrom, countryTo, cityFrom, cityTo,/* searchData, */ outDate, inDate, seats, destinations,
    exCountry, availableCountries, availableCountriesTo, availableCities, availableCitiesTo, rules, violated,
    allowedDays, allowedDaysIn, allCount,
    sendSearch, reverseCountries, cityRules, allCountries, newCountry, changeCountry, dropCountry, getCountryFrom,
    allCities, dateQuery, isDateAllowed, isDateInAllowed, dateFormat
  }

})
