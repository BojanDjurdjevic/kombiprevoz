import router from "@/router";
import { defineStore } from "pinia";
import { ref } from 'vue'


export const useUserStore = defineStore('user', () => {
    const user = ref()

    function logout() {
        user.value = null

        router.push({
            name: 'home'
        })
    }

    return {
        user,
        logout,
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