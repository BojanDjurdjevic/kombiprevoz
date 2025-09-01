import { ref, onMounted } from "vue";
import { defineStore } from "pinia";
import { useUserStore } from "./user";
import { useSearchStore } from "./search";
import api from "@/api";
import router from "@/router";
import { useRoute } from 'vue-router';


export const useMyOrdersStore = defineStore('myorders', () => {
    function dateFormat(date) {
        let d = new Date(date)
        let year = String(d.getFullYear()) 
        let months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']
        let m = d.getMonth()
        let month = months[m]
        let dates = String(d.getDate())
        let formated = dates + "." + month + "." + year 

        return formated
    }
    const route = useRoute()
    const user = useUserStore()
    const search = useSearchStore()
    const myorders = ref([])
    const oneOrder = ref({})

    function takeOrder(order) {
        if(myorders.value) {
            myorders.value.orders.forEach(item => {
                if(item.id == order.id) {
                    oneOrder.value = item
                }
            })
            console.log(oneOrder.value)
        }
    }

    onMounted(() => {
        actions.value.getUserOrders(addedOrders.value.orders)
    })

    const addressDialog = ref(false)
    const plsDialog = ref(false)
    const plsConfDialog = ref(false)
    const dateDialog = ref(false)
    const dateConfDialog = ref(false)

    const addedOrders = ref({
        orders: {
            create: [],
            user_id: false
        }
    })
    // ---------------------- UPDATE ADDRESS ------------------------ //
    const pickup = ref({
        id: '',
        add_from: '',
        add_to: ''
    })
    function clearPickup() {
        pickup.value = {
            id: pickup.value.id,
            add_from: '',
            add_to: ''
        }
    }
    function populatePickup(order) {
        pickup.value.id = order.id
        pickup.value.add_from = order.pickup
        pickup.value.add_to = order.dropoff
    }

    // ---------------------- UPDATE PLACES ------------------------ //
    const currentPrice = ref(0)
    const pricePerUnit = ref(0)
    const newPrice = ref(0)
    //const currSeats = ref(0)
    const seatsUp = ref({
        id: '',
        seats: 1
    })
    function places(order) {
        //currSeats.value = order.places
        //seatsUp.value.seats = currSeats.value
        seatsUp.value.id = order.id
        currentPrice.value = order.price 
        pricePerUnit.value = order.price / order.places
    }
    function clsSeats() {
        seatsUp.value = {
            id: '',
            seats: 1
        }
        currentPrice.value = 0
        newPrice.value = 0
        plsDialog.value = false
        plsConfDialog.value = false
    }

    function calculateNewPrice() {
        newPrice.value = pricePerUnit.value * seatsUp.value.seats
    }

    //--------------------- RESCHEDULE -----------------------//
    const currentDate = ref('')
    const currentDateIn = ref('')
    const requestDate = ref('')
    const requestDateIn = ref('')
    function onRequestDate(value) {
        requestDate.value = dateFormat(value)
    }
    function onRequestDateIn(value) {
        requestDateIn.value = dateFormat(value)
    }
    function prepareDates(cityFrom, cityTo, date) {
        search.cityFrom = {name: cityFrom}
        search.cityTo = {name: cityTo}
        currentDate.value = oneOrder.value.items[0].date
        currentDateIn.value = oneOrder.value.items[1].date
        search.dateQuery()
    }

    function clsReschedule() {
        dateConfDialog.value = false
        dateDialog.value = false
    }

    

    const actions = ref({
        getUserOrders: async (orders) => {
            if(user.user) {
                addedOrders.value.orders.user_id = user.user.id
            } else return
            user.loading = true
            try {
                const res = await api.getOrder(orders) 
                if(res.data.success) {
                    myorders.value = res.data
                    myorders.value.orders.forEach(order => {
                        order.items.forEach(item => {
                            let splited = item.date.split("-")
                            let reversed = splited.reverse()
                            let formated = reversed.join(".")
                            item.date = formated
                        })
                    })
                } 
                else {
                    user.showSucc(res, 3000)
                } 
                
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
        },
        addUpdate: async (order, old) => {
            console.log(old)
            if(!user.user) {
                return router.push({
                    name: "login",
                    query: {redirect: route.fullPath}
                })
            }
            if(!pickup.value.id || !pickup.value.add_from || !pickup.value.add_to) return
            user.loading = true
            try {
                const dto = {
                    orders: {
                        update: { id: order.id },
                        address: {
                            add_from: order.add_from,
                            add_to: order.add_to
                        },
                        user_id: user.user.id
                    }
                }
                const res = await api.orderItemUpdate(dto)
                console.log(res.data)
                if(res.data.success) user.showSucc(res, 3000)
                await actions.value.getUserOrders(addedOrders.value.orders)
                //await nextTick()
                
                router.push({
                    name: 'rezervacije'
                })
                setTimeout(() => {
                    myorders.value.orders.forEach(item => {
                        if(item.id == old.id) {
                            oneOrder.value = item
                        }
                    })
                    console.log(oneOrder.value)
                    router.push({
                        name: 'uredi'
                    })
                }, 3000)
            } catch (error) {
                console.dir(error, {depth: null})
                user.showErr(error, 3000)
            } finally {
                user.loading = false
                addressDialog.value = false
                clearPickup()
                console.log(pickup.value)
            }
            
            
        },
        changePlaces: async () => {
            user.loading = true
            const dto = {
                orders: {
                    user_id: user.user.id,
                    update: {
                        id: seatsUp.value.id
                    },
                    new_places: seatsUp.value.seats
                }
            }
            try {
                const res = await api.orderItemUpdate(dto)
                console.log("prošlo")
                console.log(res.data)
                if(res.data.success) user.showSucc(res, 3000)
                await actions.value.getUserOrders(addedOrders.value.orders)
            } catch (error) {
                console.log("error1")
                console.dir(error, {depth: null})
                user.showErr(error, 3000)
            } finally {
                user.loading = false
                clsSeats()
            }
        },
        reschedule: async () => {
            user.loading = true
        }
    })

    return {
        myorders, oneOrder, actions, addedOrders, addressDialog, plsDialog, dateDialog, pickup, seatsUp,
        currentPrice, newPrice, pricePerUnit, plsConfDialog, dateConfDialog, currentDate, currentDateIn,
        requestDate, requestDateIn,
        takeOrder, clearPickup, populatePickup, places, clsSeats, calculateNewPrice, clsReschedule,
        prepareDates, onRequestDate, onRequestDateIn, dateFormat,
    }
})