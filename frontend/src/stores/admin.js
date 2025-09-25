import { ref, onMounted } from "vue";
import { defineStore } from "pinia";
import { useUserStore } from "./user";
import { useSearchStore } from "./search";
import api from "@/api";
import router from "@/router";
import { useRoute } from 'vue-router';


export const useAdminStore = defineStore('admin', () => {

    const adminView = ref('Bookings')
    const depDay = ref({
        date: null,
        range: null
    })
    const tourID = ref(null)
    const bCode = ref(null)
    const driverID = ref(null)

    const actions = ref({
        searchByFilter: () => {
            const dto = {
                departure: depDay.value,
                tour_id: tourID.value,
                code: bCode.value,
                driver_id: driverID.value
            }
            console.log(dto)
        }
    })


    return {
        adminView, depDay, tourID, bCode, driverID,
    }

})