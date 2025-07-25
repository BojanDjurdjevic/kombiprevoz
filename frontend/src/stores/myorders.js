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
        createOrder: async (item) => {
                try {
                    const res = await api.makeOrder(item)
                    console.log(res)
                    if(res.data.success) {
                        user.successMsg = res.data.msg
                        //router.push('/rezervacije')
                    }
                    return true
                } catch(error) {
                    console.dir(error, {depth: null})
                    if(error.response.data.error) {
                        user.errorMsg = error.response.data.error
                    }
                    return false
                } finally {
                    user.loading = false
                    user.clearMsg(4000)
                }
            }
    })

    return {
        myorders, oneOrder, actions,
        takeOrder, 
    }
})