import { ref } from "vue";
import { defineStore } from "pinia";
import { useUserStore } from "./user";
import api from "@/api";
import router from "@/router";


export const useMyOrdersStore = defineStore('myorders', () => {
    const user = useUserStore()
    const myorders = ref([])
    const oneOrder = ref({})

    function takeOrder(order) {
        myorders.value.forEach(item => {
            if(item.id == order.id) {
                oneOrder.value = order
            }
        })
        
    }

    const actions = ref({
        createOrder: async (item, id) => {
                try {
                    const res = await api.makeOrder(item, id)
                    console.log(res)
                    if(res.data.success) {
                        user.showSucc(res, 6000)
                        router.push('/rezervacije')
                    }
                } catch(error) {
                    console.dir(error, {depth: null})
                    if(error.response.data.error) {
                        user.showErr(error, 6000)
                    }
                } finally {
                    user.loading = false
                }
            }
    })

    return {
        myorders, oneOrder, actions,
        takeOrder, 
    }
})