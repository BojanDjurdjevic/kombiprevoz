import { defineStore } from 'pinia'
import { ref, computed, shallowRef } from 'vue'
import { useUserStore } from './user'
import { useMyOrdersStore } from './myorders';
import router from '@/router';


export const useTourStore = defineStore('tours', () => {
    const user = useUserStore()
    const orders = useMyOrdersStore()
    const mySearch = ref({})

    const available = ref([
        {
            'id': 1,
            'from': 'Novi Sad',
            'to': 'Rijeka',
            'date': '12/04/2025',
            'departure': '08:00',
            'arrival': '14:30',
            'price': 60,
            'priceTotal': 60,
            'seats': 1
        },
        {
            'id': 2,
            'from': 'Rijeka',
            'to': 'Novi Sad',
            'date': '13/04/2025',
            'departure': '08:00',
            'arrival': '14:30',
            'price': 60,
            'priceTotal': 60,
            'seats': 1
        },
        {
            'id': 3,
            'from': 'Beograd',
            'to': 'Koper',
            'date': '12/04/2025',
            'departure': '07:00',
            'arrival': '15:30',
            'price': 75,
            'priceTotal': 75,
            'seats': 1
        },
        {
            'id': 4,
            'from': 'Novi Sad',
            'to': 'Zagreb',
            'date': '12/04/2025',
            'departure': '08:00',
            'arrival': '14:30',
            'price': 50,
            'priceTotal': 50,
            'seats': 1
        },
        {
            'id': 5,
            'from': 'Ljubljana',
            'to': 'Novi Sad',
            'date': '12/04/2025',
            'departure': '08:00',
            'arrival': '14:30',
            'price': 75,
            'priceTotal': 75,
            'seats': 1
        },
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
            if(item.from == user.user.town) {
                item.addressFrom = user.user.address
                item.addressTo = ''
            } else if(item.to == user.user.town) {
                item.addressFrom = ''
                item.addressTo = user.user.address
            } else {
                item.addressFrom = ''
                item.addressTo = ''
            }
        })
    }

    const active = ref(false)

    function addTour(t) {
        active.value = true
        
        const added = {
            id: t.id,
            from: t.from,
            to: t.to,
            date: t.date,
            time: t.departure,
            price: t.priceTotal,
            seats: t.seats,
            addressFrom: user.user.address,
            addressTo: ''
        }
        if(bookedTours.value.length > 0) {
            let sum = 0
            bookedTours.value.forEach(item => {
                if(item.id == added.id) {
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
    }
    function removeTour(id) {
        bookedTours.value.forEach(t => {
            if(t.id == id) {
                bookedTours.value.splice(bookedTours.value.indexOf(t), 1)  
            }
        })
        calculateTotal()
    }
    function removeAll() {
        while (bookedTours.value.length > 0) {
            bookedTours.value.shift()
        }
        totalPrice = 0
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
            if(item.id == t.id) {
                t.price = item.price * t.seats
            }
        })
        calculateTotal()
    }

    function book() {
        active.value = false
        router.push({
            name: 'korpa'
        })
    }

    function finishBooking() {
        bookedTours.value.forEach(item => {
            orders.myorders.push(item)
        })
        bookedTours.value = []
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