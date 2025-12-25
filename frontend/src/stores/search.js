import { ref, computed, shallowRef } from 'vue'
import { defineStore } from 'pinia'
import { useTourStore } from './tours'
import router from '@/router'
import api from '@/api'
import { useUserStore } from './user'

export const useSearchStore = defineStore('search', () => {
  const tours = useTourStore()
  const user = useUserStore()

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

      afterCountryFrom(countryFrom.value, true)
      fillToCities(cityFrom.value)
      dateQuery()
    }
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
  const tourCitiesTo = ref([])

  async function allCountries(data) {
    if(data && data.id) {
      try {
        const msg = await api.getCountries(data.id)
        let input = Object.values(msg.data.drzave)
        availableCountries.value = input
      } catch (error) {
        console.log(error)
      }
    } else {
      try {
        const msg = await api.getCountries(data)
        let input = Object.values(msg.data.drzave)
        availableCountries.value = input
      } catch (error) {
        console.log(error)
      }
    }
    
    
  }

  function getCountryFrom(c) {
    if(!c) return
    availableCountriesTo.value = []

    if(c.name === 'Srbija') {
      availableCountries.value.forEach(item => {
        if(item.name != 'Srbija') {
          availableCountriesTo.value.push(item)
        }
      });
    } else {
      availableCountries.value.forEach(item => {
        if(item.name == 'Srbija') {
          availableCountriesTo.value.push(item)
        }
      });
    }
  }

  async function allCities(c, from) {
    if(!c) return
    let dto = {
      country_id: c.id
    }
    try {
      const msg = await api.getCities(dto)
      if(from) {
        availableCities.value = msg.data.cities 
        console.log('Iz ALLCITIES: ', availableCities.value)
      } else {
        availableCitiesTo.value = Object.values(msg.data.cities)
      }
    } catch (error) {
      console.log(error)
    }
  }

  function afterCountryFrom(c, from) {
    getCountryFrom(c)
    allCities(c, from)
  }

  async function fillToCities(c) {
    cityTo.value = ''
    if(!c) return
    allCities(countryTo.value, false)
    const dto = {
      city: {
        from: true,
        name: c.name
      }
    }

    try {
      const res = await api.getTours(dto)
      tourCitiesTo.value = res.data.success ? res.data.toCities : []
      if(res.data.has_cities) {
        availableCitiesTo.value = availableCitiesTo.value.filter(c =>
          tourCitiesTo.value.some(t => t.to_city === c.name)
        )
      } else {
        availableCitiesTo.value = []
      }
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
    if(!cityFrom.value || !cityTo.value) return
    let formD = qDateForm()
    let dto = {
      days: {
        from: cityFrom.value.name,
        to: cityTo.value.name,
        format: formD
      }
    }
    if(dto.days.to === undefined) dto.days.to = cityTo.value
    console.log('Date UPIT: ', dto.days)
    try {
      const msg = await api.checkAvailableDates(dto)
      if(msg.data.success) {
        allowedDays.value.fullyBooked = msg.data.fullyBooked
        allowedDays.value.allowed = msg.data.allowed

        allowedDaysIn.value.fullyBooked = msg.data.fullyBookedIn
        allowedDaysIn.value.allowed = msg.data.allowedIn
      } else {
        availableCitiesTo.value = []
      }
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

    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    return allowedDays.value.allowed.includes(dayOfWeek) && !allowedDays.value.fullyBooked.includes(formated) && date >= new Date()
  } 
  const isDateInAllowed = (dateStr) => {
    const date = new Date(dateStr)
    const dayOfWeek = date.getDay()
    let formated = dateFormat(dateStr)

    return allowedDaysIn.value.allowed.includes(dayOfWeek) && !allowedDaysIn.value.fullyBooked.includes(formated) && date >= new Date()
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
        if(dto.search.to === undefined) {
          dto.search.to = String(cityTo.value)
        }
        console.log('Grad dolaska iz GRADA: ', dto.search.to)
        try {
          const msg = await api.getTours(dto)
          tours.available = msg.data.tour 
          console.log(tours.available)
          localStorage.setItem('avTours', JSON.stringify(tours.available))
          router.push({
            name: 'rezultati'
          })
        } catch (error) {
          console.log(error)
          dialog.value = false
          user.showErr(error, 3000)
        } finally { 
          tours.mySearch = dto

          countryFrom.value = ''
          countryTo.value = ''
          cityFrom.value = ''
          cityTo.value = ''
          outDate.value = null
          inDate.value = null
          seats.value = 1
          
          
        }
    }
  }

  return { 
    dialog, bound, countryFrom, countryTo, cityFrom, cityTo,/* searchData, */ outDate, inDate, seats, destinations,
    exCountry, availableCountries, availableCountriesTo, availableCities, availableCitiesTo, rules, violated,
    allowedDays, allowedDaysIn, allCount,
    sendSearch, reverseCountries, cityRules, allCountries, getCountryFrom,
    allCities, dateQuery, isDateAllowed, isDateInAllowed, dateFormat, qDateForm, fillToCities, afterCountryFrom,
  }

})
