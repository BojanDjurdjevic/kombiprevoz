import api from "@/api";
import router from "@/router";
import { defineStore } from "pinia";
import { useRoute } from "vue-router";
import { ref } from 'vue'


export const useUserStore = defineStore('user', () => {
    const route = useRoute()
    const user = ref(null /*{
        id: 10,
        initials: 'BD',
        fullName: 'Bojan Đurđević',
        email: 'pininfarina164@gmail.com',
        town: 'Novi Sad',
        address: 'Gavrila Principa 6',
        phone: '062640273'
    } */)
    const errorMsg = ref('')
    const loading = ref(false)

    const getters = ref({
        isAuthenticated: (state) => !! user.value
    })
    const actions = ref({
        checkSession: async () => {
            loading.value = true
            try {
                const res = await api.isLogged(true)
                if(res.data.user) {
                    user.value = res.data.user
                } else {
                    user.value = null
                }
            } catch (error) {
                errorMsg.value = res.data.error
            } finally {
                loading.value = false
            }
            
        },
        setUser: (userData) => {
            user.value = userData
        }, 
        handleLogin: async (logUser) => {
            loading.value = true
            try {
                const res = await api.logUser(logUser)
                if(res.data.success) {
                    //user.value = res.data.user
                    this.setUser(res.data.user)
                    const redirectPath = route.query.redirect || '/'
                    router.push(redirectPath)
                }
            } catch (error) {
                errorMsg.value = res.data.error
            } finally {
                loading.value = false
            }
        },
        logout: () => {
            user.value = null
        }
    })

    

    function logout() {
        user.value = null

        router.push({
            name: 'home'
        })
    }

    return {
        user, errorMsg, loading, getters, actions,
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