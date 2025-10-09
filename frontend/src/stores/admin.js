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

    const lastFetch = ref(null)   
    const lastFetch48 = ref(null) 

    const in24 = ref(null)
    const in48 = ref(null)

    const in24Search = ref('')
    const in48Search = ref('')
    const headers = [
        {key: 'from_city', title: 'Grad polaska'},
        {key: 'to_city', title: 'Grad dolaska'},
        {key: 'pickuptime', title: 'Vreme polaska'},
        {key: 'total_places', title: 'Broj putnika'},
        {key: 'actions', title: 'Dodaj slobodnog vozača', sortable: false},
        {key: 'assign', title: 'Dodeli vozača', sortable: false},
        {key: 'details', title: 'Detalji', sortable: false}
    ]

    const drivers_24 = ref({})
    const drivers_48 = ref({})

    const assignedDriverID_24 = ref(null)
    const assignedDriverID_48 = ref(null) 

    // SEARCH BOOKINGS BY FILTERS

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
                        in48: ""
                    }
                }
                try {
                    const res = await api.getOrder(dto)
                    in24.value = res.data
                    drivers_24.value = res.data.drivers
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
                        in48: true,
                        in24: ""
                    }
                }
                try {
                    const res = await api.getOrder(dto)
                    in48.value = res.data 
                    drivers_48.value = res.data.drivers 
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
        openTour: (item) => {
            console.log(item)
        },
        assignDriver: async (driver, tour_id, rides) => {
            const dto = {
                orders: {
                    user_id: user.user.id,
                    driver: driver,
                    tour_id: tour_id,
                    selected: rides
                }
            }
            if(!dto.orders.driver || !dto.orders.selected || !dto.orders.tour_id) {
                user.errorMsg = "Proverite sve podatke, nije moguće dodeliti vozača!"
                user.clearMsg(3000)
            }
            
            try {
                loading.value = true
                const res = await api.orderItemUpdate(dto)
                user.showSucc(res, 3000)
                console.log(res.data)
            } catch (error) {
                console.log(error)
            } finally {
                loading.value = false
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
        in24Search, headers, in48Search, in24, in48, drivers_24, drivers_48, assignedDriverID_24,
        assignedDriverID_48,
        
    }

})