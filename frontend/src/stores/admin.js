import { ref, onMounted, computed } from "vue";
import { defineStore } from "pinia";
import { useUserStore } from "./user";
import { useSearchStore } from "./search";
import api from "@/api";
import router from "@/router";
import { useRoute } from 'vue-router';
import europeCities from '@/data/country-city.json'


export const useAdminStore = defineStore('admin', () => {
    const user = useUserStore()

    const adminView = ref('Bookings')
    const loading = ref(false)

    // BOOKINGS
    const tab_bookings = ref(null);

    const items_bookings = [
        "Pretraga",
        "U narednih 24h",
        "U narednih 48h"
    ];

    const depDay = ref({
        date: null,
        range: null
    })
    const tourID = ref(null)
    const bCode = ref(null)
    const driverID = ref(null)

    const tours = ref([
        {id: 1, name: 'Novi Sad - Rijeka'},
        {id: 2, name: 'Rijeka - Novi Sad'}
    ])

    const lastFetch = ref(null)   
    const lastFetch48 = ref(null) 

    const in24 = ref(null)
    const in48 = ref(null)

    // USERS
    const usrEmail = ref(null)

    // TOURS
    const tourName = ref(null)
    const toursFrom = ref(null)
    const toursTo = ref(null)

    const toAddCountry = ref(null)
    const selectedCountry = ref(null)
    const selectedCity = ref(null)
    const cityOptions = computed(() => {
        if(!selectedCountry.value) return []
        const countryData = europeCities.find(c => c.country === selectedCountry.value)
        return countryData ? countryData.cities : []
    })

    const actions = ref({
        searchBooking: () => {
            const dto = {
                departure: depDay.value,
                tour_id: tourID.value,
                code: bCode.value,
                driver_id: driverID.value
            }
            console.log(dto)
        },
        fetchBookings: async (tab) => {
            if(tab == 'U narednih 24h') {
                loading.value = true
                const now = Date.now()
                if(lastFetch.value && lastFetch.value >= now - 6 * 60 * 1000) return
                const dto = {
                    user_id: user.user.id,
                    adminOrders: {
                        all: true,
                        in24: true,
                        in48: false
                    }
                }
                try {
                    const res = await api.getOrder(dto)
                    in24.value = res.data
                    console.log(in24.value)
                    lastFetch.value = now
                } catch (error) {
                    console.log(error)
                } finally {
                    loading.value = false
                }
            } else if(tab == 'U narednih 48h') {
                loading.value = true
                const now = Date.now()
                if(lastFetch48.value && lastFetch48.value >= now - 6 * 60 * 1000) return
                const dto = {
                    user_id: user.user.id,
                    adminOrders: {
                        all: true,
                        in24: null,
                        in48: true
                    }
                }
                try {
                    const res = await api.getOrder(dto)
                    in48.value = res.data 
                    console.log(in48.value)
                    lastFetch48.value = now
                } catch (error) {
                    console.log(error)
                } finally {
                    loading.value = false
                }
            } else {
                return
            }
            
        },
        searchUser: () => {
            const dto = {
                email: usrEmail.value
            }
            console.log(dto)
        },
        searchTour: () => {
            const dto = {
                title: tourName.value,
                t_from: toursFrom.value,
                t_to: toursTo.value
            }
            console.log(dto)
        }
    })


    return {
        actions,
        adminView, depDay, tourID, bCode, driverID, tours, usrEmail, tourName, toursFrom, toursTo,
        selectedCity, selectedCountry, cityOptions, toAddCountry, tab_bookings, items_bookings,
        
    }

})