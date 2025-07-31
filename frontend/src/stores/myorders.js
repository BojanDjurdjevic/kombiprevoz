import { ref, onMounted } from "vue";
import { defineStore } from "pinia";
import { useUserStore } from "./user";
import api from "@/api";
import router from "@/router";


export const useMyOrdersStore = defineStore('myorders', () => {
    const user = useUserStore()
    const myorders = ref([])
    const oneOrder = ref({})

    function takeOrder(order) {
        myorders.value.orders.forEach(item => {
            if(item.id == order.id) {
                oneOrder.value = order
            }
        })
        
    }

    const addedOrders = ref({
        orders: {
            create: [],
            user_id: user.user.id
        }
    })

    onMounted(() => {
        actions.value.getUserOrders(addedOrders.value.orders)
    })

    const actions = ref({
        getUserOrders: async (orders) => {
            user.loading = true
            try {
                const res = await api.getOrder(orders) 
                if(res.data.success) myorders.value = res.data
                else {
                    user.showSucc(res, 3000)
                } 
                myorders.value.orders.forEach(order => {
                    order.items.forEach(item => {
                        let splited = item.date.split("-")
                        let reversed = splited.reverse()
                        let formated = reversed.join(".")
                        item.date = formated
                    })
                })
                console.log(myorders.value)
                console.log(res.data)
            } catch (error) {
                console.dir(error, {depth: null})
            } finally {
                user.loading = false
            }
        },
        createOrder: async (tour) => {
            try {
                const res = await api.makeOrder(tour)
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
        myorders, oneOrder, actions, addedOrders,
        takeOrder,
    }
})