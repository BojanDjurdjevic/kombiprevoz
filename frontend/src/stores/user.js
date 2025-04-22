import { defineStore } from "pinia";
import { ref } from 'vue'


export const useUserStore = defineStore('user', () => {
    const user = ref({
        initials: 'BD',
        fullName: 'Bojan Đurđević',
        email: 'bojan@test.com',
        town: 'Novi Sad',
        address: 'Gavrila Principa 6',
        phone: '062640273'
    })

    return {
        user,
    }
})

/**
 {
        initials: 'BD',
        fullName: 'Bojan Đurđević',
        email: 'bojan@test.com',
        town: 'Novi Sad',
        address: 'Gavrila Principa 6',
        phone: '062640273'
    }
 */