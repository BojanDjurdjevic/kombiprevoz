import { ref } from "vue";
import { defineStore } from "pinia";


export const useMyOrdersStore = defineStore('myorders', () => {
    const myorders = ref([])
    const oneOrder = ref({})

    function takeOrder(order) {
        myorders.value.forEach(item => {
            if(item.id == order.id) {
                oneOrder.value = order
            }
        })
        
    }

    return {
        myorders, oneOrder,
        takeOrder,
    }
})