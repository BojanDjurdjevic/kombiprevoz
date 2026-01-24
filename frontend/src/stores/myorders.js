import { ref, onMounted } from "vue";
import { defineStore } from "pinia";
import { useUserStore } from "./user";
import { useSearchStore } from "./search";
import api from "@/api";
import router from "@/router";
import { useRoute } from 'vue-router';
import { useAdminStore } from "./admin";


export const useMyOrdersStore = defineStore('myorders', () => {
    function displayError(
        str = "Došlo je do greške! Neke akcije nisu dozvoljene u Demo režimu"
    ) {
        user.errorMsg = str;
        user.clearMsg(3000)
    }

    function displaySuccess(
        str = "Akcija je uspešno izvršena!"
    ) {
        user.successMsg = str;
        user.clearMsg(3000)
    }


    function dateFormat(date, view) {
        let d = new Date(date)
        let year = String(d.getFullYear()) 
        let months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12']
        let m = d.getMonth()
        let month = months[m]
        let dates = String(d.getDate())
        dates = dates.length === 1 ? "0" + dates : dates 
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
    if(user.user?.is_demo) myorders.value = demoOrdersLS.value

    function takeOrder(order) {
        if(myorders.value) {
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
        add_to: '',
        date: null,
        from: null,
        places: null,
        price: null,
        time: null,
        to: null,
        tour_id: null,
        user_id: null
    })
    function clearPickup() {
        pickup.value = {
            id: pickup.value.id,
            add_from: '',
            add_to: '',
            date: null,
            from: null,
            places: null,
            price: null,
            time: null,
            to: null,
            tour_id: null,
            user_id: null
        }
    }
    function populatePickup(order) {
        console.log('Iz propsa: ', order)
        pickup.value.id = order.id
        pickup.value.add_from = !user.user.is_demo ? order.pickup : order.add_from
        pickup.value.add_to = !user.user.is_demo ? order.dropoff : order.add_to

        if(user.user.is_demo) {
            pickup.value.date = order.date
            pickup.value.from = order.from
            pickup.value.places = order.places
            pickup.value.price = order.price
            pickup.value.time = order.time
            pickup.value.to = order.to
            pickup.value.tour_id = order.tour_id
            pickup.value.user_id = order.user_id
        }
    }

    // ---------------------- UPDATE PLACES ------------------------ //
    const demoOrderItemLS = ref({
        add_from: '',
        add_to: '',
        date: null,
        from: '',
        id: null,
        places: null,
        price: null,
        time: '',
        to: '',
        tour_id: null,
        user_id: null
    })
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
        seatsUp.value.seats = order.places
        currentPrice.value = order.price 
        pricePerUnit.value = order.price / order.places
        if(user.user?.is_demo) {
            demoOrderItemLS.value = {
                add_from: order.add_from,
                add_to: order.add_to,
                date: order.date,
                from: order.from,
                id: order.id,
                places: order.places,
                price: order.price,
                time: order.time,
                to: order.to,
                tour_id: order.tour_id,
                user_id: order.user_id
            }
        }
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

        demoOrderItemLS.value = {
            add_from: '',
            add_to: '',
            date: null,
            from: '',
            id: null,
            places: null,
            price: null,
            time: '',
            to: '',
            tour_id: null,
            user_id: null
        }
    }

    function calculateNewPrice() {
        newPrice.value = pricePerUnit.value * seatsUp.value.seats

        if(user.user?.is_demo) {
            demoOrderItemLS.value.price = newPrice.value
            demoOrderItemLS.value.places = seatsUp.value.seats
        }
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
        if(!value) {
            requestDate.value = ''
            return
        }
        requestDate.value = dateFormat(value, false)
        requestDateView.value = dateFormat(value, true)
    }
    function onRequestDateIn(value) {
        if(!value) {
            requestDateIn.value = ''
            return
        }
        requestDateIn.value = dateFormat(value, false)
        requestDateInView.value = dateFormat(value, true)
    }
    function prepareDates(cityFrom, cityTo, id) {
        search.cityFrom = {name: cityFrom}
        search.cityTo = {name: cityTo}
        currentDate.value = oneOrder.value.items[0].date
        currentDateIn.value = oneOrder.value.items[1].date
        itemID.value = id
        search.dateQuery()
    }

    const demoOrdIndex = ref(null)
    const indIn = ref(null)
    const indOut = ref(null)

    function prepareDemoDates(item) {
        demoOrderItemLS.value = {
            add_from: item.add_from,
            add_to: item.add_to,
            date: item.date,
            from: item.from,
            id: item.id,
            places: item.places,
            price: item.price,
            time: item.time,
            to: item.to,
            tour_id: item.tour_id,
            user_id: item.user_id
        }
        search.cityFrom = {name: item.from}
        search.cityTo = {name: item.to}
        currentDate.value = item.date
        demoOrdIndex.value = demoOrdersLS.value.findIndex(o => o.id === item.id)
        indIn.value = demoOrdersLS.value[demoOrdIndex.value].orders.findIndex(i => i.tour_id === item.tour_id)
        if(demoOrdersLS.value[demoOrdIndex.value].orders.length > 1) {
            indOut.value = demoOrdersLS.value[demoOrdIndex.value].orders.findIndex(i => i.tour_id !== item.tour_id)
            currentDateIn.value = demoOrdersLS.value[demoOrdIndex.value].orders[indOut.value].date
        } else {
            currentDateIn.value = currentDate.value
        }
        itemID.value = item.id
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

        demoOrderItemLS.value = {
            add_from: '',
            add_to: '',
            date: null,
            from: '',
            id: null,
            places: null,
            price: null,
            time: '',
            to: '',
            tour_id: null,
            user_id: null
        }
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
    const demo_order_id = ref(null)
    /*
    function deleteRequest(id) {
        delDialog.value = true
        item_id.value = id
    } */

    function deleteRequest(order) {
        delDialog.value = true
        item_id.value = !user.user?.is_demo ? order.id : order.tour_id
        demo_order_id.value = user.user?.is_demo ? order.id : null

        if(user.user?.is_demo) {
            demoOrderItemLS.value = {
                add_from: order.add_from,
                add_to: order.add_to,
                date: order.date,
                from: order.from,
                id: order.id,
                places: order.places,
                price: order.price,
                time: order.time,
                to: order.to,
                tour_id: order.tour_id,
                user_id: order.user_id,
                deleted: 0
            }
        }
        
    }

    function deleteDeny() {
        delDialog.value = false
        item_id.value = null

        demoOrderItemLS.value = {
            add_from: '',
            add_to: '',
            date: null,
            from: '',
            id: null,
            places: null,
            price: null,
            time: '',
            to: '',
            tour_id: null,
            user_id: null,
            deleted: 0
        }
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
            // DEMO FAKE create
            if(user.user?.is_demo) {
                let fakeOrder = { id: Date.now(), total: 0, ...tour} 

                fakeOrder.orders[0].tour_id = fakeOrder.id
                fakeOrder.orders[0].id = fakeOrder.id
                if(fakeOrder.orders[1]) {
                    fakeOrder.orders[1].tour_id = fakeOrder.id + 1
                    fakeOrder.orders[1].id = fakeOrder.id
                    fakeOrder.total = fakeOrder.orders[0].price + fakeOrder.orders[1].price
                } else {
                    fakeOrder.total = fakeOrder.orders[0].price
                }
                //
                demoOrdersLS.value.push(fakeOrder)
                console.log('Tura za Lokal: ', demoOrdersLS.value)
                localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                //myorders.value.push(fakeOrder)
                displaySuccess('Vaša rezervacija je uspešno kreirana i sačuvana lokalno.')
                user.loading = false
                router.push('/rezervacije')
                return;
            }

            try {
                const res = await api.makeOrder(tour)
                console.log(res)
                if(res.data.success) {
                    user.successMsg = res.data.msg
                    //router.push('/rezervacije')
                    actions.value.getUserOrders()
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
        addUpdate: async (order, tour_id) => {
            // demo fake update
            if(user.user?.is_demo) { 
                if(!pickup.value.id || !pickup.value.add_from || !pickup.value.add_to) return
                const idx = demoOrdersLS.value.findIndex(o => o.id === order.id)
                const itemIdx = demoOrdersLS.value[idx].orders.findIndex(i => i.tour_id === tour_id)

                if(idx > -1 && itemIdx > -1) {
                    demoOrdersLS.value[idx].orders[itemIdx] = { ...order }
                    localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                    myorders.value[idx].orders[itemIdx] = demoOrdersLS.value[idx].orders[itemIdx]
                    addressDialog.value = false  
                    displaySuccess('Promena adresa je uspešno izvršena i sačuvana lokalno.')
                    router.push({path: '/rezervacije'})
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
                
                router.push({
                    name: 'rezervacije'
                })
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
                console.log('changePlaces - ORD: ', demoOrderItemLS.value)
                const idx = demoOrdersLS.value.findIndex(o => o.id === demoOrderItemLS.value.id)
                const itemIdx = demoOrdersLS.value[idx].orders.findIndex(i => i.tour_id === demoOrderItemLS.value.tour_id)

                if(idx > -1 && itemIdx > -1) {
                    let total = 0

                    demoOrdersLS.value[idx].orders[itemIdx] = demoOrderItemLS.value
                    demoOrdersLS.value[idx].orders.forEach(i => {
                        total = total + i.price
                    })
                    demoOrdersLS.value[idx].total = total
                    localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                    myorders.value = demoOrdersLS.value
                    plsDialog.value = false  
                    displaySuccess('Promena broja mesta je uspešno izvršena i sačuvana lokalno.')
                    router.push({path: '/rezervacije'})
                }
                return
            }

            
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
                if(demoOrdIndex.value > -1 && indIn.value > -1 && indOut.value != null) {
                    if(!requestDate.value || !requestDateIn.value) {
                        return displayError('Datum polaska je uvek obavezno polje! Datum povratka, samo ukoliko imate rezervaciju sa povratkom! Molimo vas da popunite odgovarajuća polja.')
                    }
                    demoOrdersLS.value[demoOrdIndex.value].orders[indIn.value].date = requestDate.value
                    demoOrdersLS.value[demoOrdIndex.value].orders[indOut.value].date = requestDateIn.value

                    localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                    myorders.value= demoOrdersLS.value
                    displaySuccess('Demo update datuma je uspešno izvršen lokalno.')
                }
                if(demoOrdIndex.value > -1 && indIn.value > -1) {
                    if(!requestDate.value) {
                        return displayError('Datum polaska je uvek obavezno polje! Datum povratka, samo ukoliko imate rezervaciju sa povratkom! Molimo vas da popunite odgovarajuća polja.')
                    }
                    demoOrdersLS.value[demoOrdIndex.value].orders[indIn.value].date = requestDate.value

                    localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                    myorders.value = demoOrdersLS.value
                    displaySuccess('Demo update datuma je uspešno izvršen lokalno.')
                    
                } else {
                    displayError('Datum polaska je uvek obavezno polje! Datum povratka, samo ukoliko imate rezervaciju sa povratkom! Molimo vas da popunite odgovarajuća polja.')
                }
                router.push({path: '/rezervacije'})
                clsReschedule()
                return
            }

            onRequestDate(requestDate.value)
            onRequestDateIn(requestDateIn.value)
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
            console.log(dto)
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
                const idx = demoOrdersLS.value.findIndex(o => o.id === demo_order_id.value)
                const itemIdx = demoOrdersLS.value[idx].orders.findIndex(i => i.tour_id === item_id.value)
                console.log(
                    'Item: ', demoOrderItemLS.value, "\n",
                    'idx: ', idx, "\n",
                    'itemIdx: ', itemIdx, "\n",
                    'Item_id: ', item_id.value, "\n",
                    'Items arr: ', demoOrdersLS.value[idx].orders, "\n",
                )
                
                if(idx > -1 && itemIdx > -1) {
                    demoOrdersLS.value[idx].orders[itemIdx] = demoOrderItemLS.value
                    demoOrdersLS.value[idx].orders[itemIdx].deleted = 1
                    let totalItems = 0
                    let totalDeleted = 0
                    demoOrdersLS.value[idx].orders.forEach(item => {
                        totalItems++
                        if(item.deleted) totalDeleted++
                    })
                    if(totalItems === totalDeleted) {
                        demoOrdersLS.value.splice(idx, 1)
                    }
                    localStorage.setItem('demoOrders', JSON.stringify(demoOrdersLS.value))
                    myorders.value = demoOrdersLS.value
                    displaySuccess('Uspešno ste obrisali rezervaciju!')
                } else {
                    displayError('Nije moguće obrisati rezervaciju!')
                }
                deleteDeny()
                router.push({path: '/rezervacije'})
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
        openMyOrderLogs, prepareDemoDates,
    }
})