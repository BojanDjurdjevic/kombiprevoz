import { defineStore } from 'pinia'
import { ref, computed, shallowRef } from 'vue'
import { useUserStore } from './user'
import { useMyOrdersStore } from './myorders';
import router from '@/router';
import { useRoute } from 'vue-router';
import api from '@/api';
import { useSearchStore } from './search';


export const useTourStore = defineStore('tours', () => {
    const route = useRoute()
    const search = useSearchStore()
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

    function finishBooking() {
        //user.loading = true
        let sum = 0
        bookedTours.value.forEach(item => {
            if(item.add_from == "" || item.add_to == "") {
                sum++
                return
            }
        })
        if(sum) return

        bookedTours.value.forEach(item => {
            let splitedDate = item.date.split(".")
            let splitedDateRev = splitedDate.reverse()
            let newStr = splitedDateRev.join("-")
            item.date = newStr
            item.add_from = item.add_from.trim()
            item.add_to = item.add_to.trim()
        })

        orders.addedOrders.orders.create = bookedTours.value
        orders.addedOrders.orders.user_id = user.user.id
        bookedTours.value = []
        
        if(orders.actions.createOrder(orders.addedOrders)) {
            localStorage.removeItem('avTours')
            localStorage.removeItem('myCart') 
            //router.push('/rezervacije')
        }
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