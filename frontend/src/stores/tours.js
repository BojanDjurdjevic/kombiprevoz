import { defineStore } from 'pinia'
import { ref, computed, shallowRef } from 'vue'
import { useUserStore } from './user'
import { useMyOrdersStore } from './myorders';
import router from '@/router';
import { useRoute } from 'vue-router';


export const useTourStore = defineStore('tours', () => {
    const route = useRoute()
    const user = useUserStore()
    const orders = useMyOrdersStore()
    const mySearch = ref({})

    const available = ref([
        
    ])

    const bookedTours = ref([])
    const totalPrice = ref(0)
    function calculateTotal() {
        let total = 0
        bookedTours.value.forEach(t => {
            total += Number(t.price)
        })
        totalPrice.value = total
        console.log('total: ', totalPrice.value)
    }

    function customAddress() {
        bookedTours.value.forEach(item => {
            if(item.from == user.user.city) {
                item.add_from = user.user.address
                item.add_to = ''
            } else if(item.to == user.user.city) {
                item.add_from = ''
                item.add_to = user.user.address
            } else {
                item.add_from = ''
                item.add_to = ''
            }
        })
    }

    const active = ref(false)

    function addTour(t) {
        
        if(!user.user) {
            alert('Niste ulogovani! Molimo da se ulogujete')
            const myRoute = route.fullPath
            router.push({
                name: 'login',
                query: { redirect: myRoute }
            })
            return
        } 
        active.value = true
        
        const added = {
            tour_id: t.id,
            user_id: user.user.id,
            from: t.from,
            to: t.to,
            date: t.date,
            time: t.departure,
            price: t.priceTotal,
            places: t.seats,
            add_from: '',
            add_to: '',
            left: t.left
        }
        if(bookedTours.value.length > 0) {
            let sum = 0
            bookedTours.value.forEach(item => {
                if(item.tour_id == added.tour_id) {
                    sum++
                }
            })
            if(sum === 0) {
                bookedTours.value.push(added)
            } else {
                alert(`Već ste dodali ovu vožnju u korpu`)
                active.value = false
            }
            
        } else {
            bookedTours.value.push(added)
        }
        calculateTotal()
        customAddress()
        console.log(bookedTours.value)
    }
    function removeTour(id) {
        bookedTours.value.forEach(t => {
            if(t.tour_id == id) {
                bookedTours.value.splice(bookedTours.value.indexOf(t), 1)  
            }
        })
        calculateTotal()
    }
    function removeAll() {
        while (bookedTours.value.length > 0) {
            bookedTours.value.shift()
        }
        totalPrice.value = 0
        localStorage.removeItem('myCart')
        localStorage.removeItem('avTours')
    }

    function countSeats(id) {
        available.value.forEach(item => {
            if(item.id == id) {
                item.priceTotal = item.price * item.seats
            }
        })
    }
    function countChangeSeats(t) {
        available.value.forEach(item => {
            if(item.id == t.tour_id) {
                t.price = item.price * t.places
            }
        })
        calculateTotal()
        localStorage.setItem('myCart', JSON.stringify(bookedTours.value))
    }

    function book() {
        if(!user.user) {
            alert('Niste ulogovani! Molimo da se ulogujete')
            const myRoute = route.fullPath
            router.push({
                name: 'login',
                query: { redirect: myRoute }
            })
            return
        }
        active.value = false
        localStorage.setItem('myCart', JSON.stringify(bookedTours.value))
        router.push({
            name: 'korpa'
        })
    }

    const myOrder = ref({
        orders: {
            create: {

            }
        }
    })

    function finishBooking() {
        console.log("Niz: ", bookedTours.value)
        bookedTours.value.forEach(item => {
            console.log("jedan: ", item)
            myOrder.value.orders.create = item
            console.log("Item: ", myOrder.value)
            orders.myorders.push(myOrder.value)
        })
        bookedTours.value = []
        localStorage.removeItem('avTours')
        localStorage.removeItem('myCart') /*
        console.log(orders.myorders)
        orders.myorders.forEach(item => {
            console.log(item)
        }) */
        router.push({
            name: 'rezervacije'
        })
    }

    return {
        mySearch, available, bookedTours, totalPrice, active,
        calculateTotal, addTour, removeTour, removeAll, countSeats, countChangeSeats, book, finishBooking,
    }
})

/**
 'countryFrom' : '',
'countryTo' : '',
'cityFrom' : '',
'cityTo' : '',
'outbound': null,
'inbound': null,
'seats': 1
 */