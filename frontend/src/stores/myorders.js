import { ref } from "vue";
import { defineStore } from "pinia";


export const useMyOrdersStore = defineStore('myorders', () => {
    const myorders = ref([])

    return {
        myorders,
    }
})