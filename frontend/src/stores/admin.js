import { ref, onMounted } from "vue";
import { defineStore } from "pinia";
import { useUserStore } from "./user";
import { useSearchStore } from "./search";
import api from "@/api";
import router from "@/router";
import { useRoute } from 'vue-router';


export const useAdminStore = defineStore('admin', () => {

    const adminView = ref('Bookings')

    // BOOKINGS
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
        adminView, depDay, tourID, bCode, driverID, tours, usrEmail, tourName, toursFrom, toursTo,
        
    }

})