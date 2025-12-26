import { ref, onMounted } from "vue";
import { defineStore } from "pinia";
import { useUserStore } from "./user";
import { useSearchStore } from "./search";
import api from "@/api";
import router from "@/router";
import { useRoute } from 'vue-router';
import { useAdminStore } from "./admin";


export const useMyOrdersStore = defineStore('myorders', () => {
    function dateFormat(date, view) {
        let d = new Date(date)
        let year = String(d.getFullYear()) 
        let months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']
        let m = d.getMonth()
        let month = months[m]
        let dates = String(d.getDate())
        let formated
        if(view) formated = dates + "." + month + "." + year 
        else formated = year  + "-" + month + "-" + dates

        return formated
    }
    const route = useRoute()
    const user = useUserStore()
    const search = useSearchStore()
    const admin = useAdminStore()
    const myorders = ref([])
    const oneOrder = ref({})

    const demoOrdersLS = ref(JSON.parse(localStorage.getItem('demoOrders') || '[]'))

    function takeOrder(order) {
        if(myorders.value.length) {
            if(user.user.is_demo) {
                myorders.value.forEach(item => {
                    if(item.id == order.id) {
                        oneOrder.value = item
                    }
                })
                console.log(oneOrder.value)
                return;
            }
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
    const delDialog = ref(false)

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
        console.log('Iz propsa: ', order)
        pickup.value.id = !user.user.is_demo ? order.id : order.tour_id
        pickup.value.add_from = !user.user.is_demo ? order.pickup : order.add_from
        pickup.value.add_to = !user.user.is_demo ? order.dropoff : order.add_to
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
    const itemID = ref(null)
    const currentDate = ref('')
    const currentDateIn = ref('')
    const requestDate = ref(null)
    const requestDateView = ref(null)
    const requestDateIn = ref(null)
    const requestDateInView = ref(null)
    function onRequestDate(value) {
        requestDate.value = dateFormat(value, false)
        requestDateView.value = dateFormat(value, true)
    }
    function onRequestDateIn(value) {
        requestDateIn.value = dateFormat(value, false)
        requestDateInView.value = dateFormat(value, true)
    }
    function prepareDates(cityFrom, cityTo, id) {
        search.cityFrom = {name: cityFrom}
        search.cityTo = {name: cityTo}
        currentDate.value = !user.user.is_demo ? oneOrder.value.items[0].date : oneOrder.value.orders.create[0].date
        currentDateIn.value = !user.user.is_demo ? oneOrder.value.items[1].date : oneOrder.value.orders.create[1].date
        itemID.value = id
        search.dateQuery()
    }

    function clsReschedule() {
        dateConfDialog.value = false
        dateDialog.value = false
        requestDate.value = null
        requestDateIn.value = null
        requestDateView.value = null
        requestDateInView.value = null
        itemID.value = null
    }

    function checkDates() {
        if(!requestDate.value && !requestDateIn.value) {
            dateConfDialog.value = false
        } else dateConfDialog.value = true
    }

    // --------------------------- ORDER LOGS --------------------//

    const myOrderLogs = ref([])

    function openMyOrderLogs(order) {
        console.log("OrderID: ", order.id, "\n", 'Logovi ordera: ', order.logs)
        myOrderLogs.value = order.logs
        setTimeout(() => {
            admin.orderHistoryDialog = true
        }, 100);
    }

    // --------------------------- DELETE ----------------------- //
    const item_id = ref(null)

    function deleteRequest(id) {
        delDialog.value = true
        item_id.value = id
    }

    function deleteDeny() {
        delDialog.value = false
        item_id.value = null
    }

    
    // ------------------- ALL ACTON METHODS -------------------- //
    const actions = ref({
        getUserOrders: async (orders) => {
            if(user.user) {
                addedOrders.value.orders.user_id = user.user.id
            } else return

            // DEMO read
            if(user.user?.is_demo) {
                myorders.value = demoOrdersLS.value
                return
            }

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
            console.log('Tura iz carta: ', tour)
            // DEMO FAKE create
            if(user.user?.is_demo) {
                let fakeOrder = { id: Date.now(), total: 0, ...tour}
                fakeOrder.orders.create[0].tour_id = fakeOrder.id
                if(fakeOrder.orders.create[1]) {
                    fakeOrder.orders.create[1].tour_id = fakeOrder.id + 1
                    fakeOrder.total = fakeOrder.orders.create[0].price + fakeOrder.orders.create[1].price
                } else {
                    fakeOrder.total = fakeOrder.orders.create[0].price
                }
                demoOrdersLS.value.push(fakeOrder)
                console.log('Tura za Lokal: ', demoOrdersLS.value)
                localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                myorders.value.push(fakeOrder)
                user.successMsg = 'Demo rezervacija kreirana lokalno i privremeno.'
                user.loading = false
                return;
            }

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
            // demo fake update
            if(user.user?.is_demo) { 
                if(!pickup.value.id || !pickup.value.add_from || !pickup.value.add_to) return
                console.log('iz fake UPDATE: ', order, old)
                return
                const idx = demoOrdersLS.value.findIndex(o => o.id === order.id)
                if(idx > -1) {
                    demoOrdersLS.value[idx] = { ...demoOrdersLS.value[idx], ...order }
                    localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                    myorders.value.orders[idx] = demoOrdersLS.value[idx]
                    user.successMsg = 'Demo update izvršen lokalno.'
                }
                return
            }

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
            //demo fake changePlaces
            if(user.user?.is_demo) { 
                const idx = demoOrdersLS.value.findIndex(o => o.id === seatsUp.value.id)
                if(idx > -1) {
                    demoOrdersLS.value[idx].places = seatsUp.value.seats
                    localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                    myorders.value.orders[idx] = demoOrdersLS.value[idx]
                    user.successMsg = 'Demo promena mesta izvršena lokalno'
                }
                clsSeats()
                return
            }

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
            clsSeats()
            try {
                const res = await api.orderItemUpdate(dto)
                console.log(res.data)
                if(res.data.success) user.showSucc(res, 3000)
                await actions.value.getUserOrders(addedOrders.value.orders)
                router.push('/rezervacije')
            } catch (error) {
                console.dir(error, {depth: null})
                user.showErr(error, 3000)
            } finally {
                user.loading = false
                
            }
        },
        reschedule: async () => {
            // DEMO fake reschedule
            if(user.user?.is_demo) { 
                const idx = demoOrdersLS.value.findIndex(o => o.id === itemID.value)
                if(idx > -1) {
                    demoOrdersLS.value[idx].date = requestDate.value
                    demoOrdersLS.value[idx].dateIn = requestDateIn.value
                    localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                    myorders.value.orders[idx] = demoOrdersLS.value[idx]
                    user.successMsg = 'Demo update datuma izvršen lokalno.'
                }
                clsReschedule()
                return
            }

            const dto = {
                orders: {
                    user_id: user.user.id,
                    update: {
                        id: itemID.value
                    },
                    reschedule: {
                        outDate: requestDate.value,
                        inDate: requestDateIn.value
                    }
                }
            }
            try {
                const res = await api.orderItemUpdate(dto)
                console.log(res.data)
                if(res.data.success) user.showSucc(res, 3000)
                await actions.value.getUserOrders(addedOrders.value.orders)
                router.push('/rezervacije')
            } catch (error) {
                console.dir(error, {depth: null})
                user.showErr(error, 3000)
            } finally {
                clsReschedule()
                user.loading = false
            }
        }, 
        cancel: async () => {
            // DEMO fake cancel
            if(user.user?.is_demo) { 
                const idx = demoOrdersLS.value.findIndex(o => o.id === item_id.value)
                if(idx > -1) {
                    demoOrdersLS.value.splice(idx, 1)
                    localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                    myorders.value.orders = [...demoOrdersLS.value]
                    user.successMsg = 'Demo rezervacija obrisana lokalno'
                }
                deleteDeny()
                return
            }

            const dto = {
                orders: {
                    user_id: user.user.id,
                    delete: {
                        item_id: item_id.value
                    }
                }
            }
            try {
                const res = await api.orderItemDelete(dto)
                console.log(res.data)
                if(res.data.success) user.showSucc(res, 3000)
                await actions.value.getUserOrders(addedOrders.value.orders)
                router.push('/rezervacije')
            } catch (error) {
                console.dir(error, {depth: null})
                user.showErr(error, 3000)
            } finally {
                deleteDeny()
                user.loading = false
            }
        }
    })

    return {
        myorders, oneOrder, actions, addedOrders, addressDialog, plsDialog, dateDialog, pickup, seatsUp,
        currentPrice, newPrice, pricePerUnit, plsConfDialog, dateConfDialog, currentDate, currentDateIn,
        requestDate, requestDateIn, requestDateView, requestDateInView, delDialog, myOrderLogs,
        demoOrdersLS,

        takeOrder, clearPickup, populatePickup, places, clsSeats, calculateNewPrice, clsReschedule,
        prepareDates, onRequestDate, onRequestDateIn, dateFormat, checkDates, deleteRequest, deleteDeny,
        openMyOrderLogs,
    }
})