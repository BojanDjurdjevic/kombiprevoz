import api from "@/api";
import router from "@/router";
import { defineStore } from "pinia";
import { useRoute } from "vue-router";
import { ref } from 'vue'


export const useUserStore = defineStore('user', () => {
    const route = useRoute()
    const isLoggedUser = ref({
        user: true
    })
    const user = ref(null /*{
        id: 10,
        initials: 'BD',
        fullName: 'Bojan Đurđević',
        email: 'pininfarina164@gmail.com',
        town: 'Novi Sad',
        address: 'Gavrila Principa 6',
        phone: '062640273'
    } */)

    const rules = {
        required: (value) => !!value || "Obavezno polje.",
        counter: (value) => value.length <= 21 || "Maksimum 21 karakter",
        email: (value) => {
            const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
            
            return pattern.test(value) || 'Neadekvatan e-mail.'
        },
        password: (value) => {
            const pattern = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}$/

            return pattern.test(value) || 'Neadekvatna Lozinka. '
        }
    }

    const errorMsg = ref(false)
    const successMsg = ref(false)
    const loading = ref(false)

    const getters = ref({
        isAuthenticated: (value) => !! value || false
    })
    const actions = ref({
        checkSession: async () => {
            isLoggedUser.value.user = true
            loading.value = true
            try {
                const res = await api.isLogged(isLoggedUser.value)
                if(res.data.user) {
                    user.value = res.data.user
                } else {
                    user.value = null
                }
            } catch (error) {
                console.log(error)
                errorMsg.value = res.data.error
                
            } finally {
                loading.value = false
            }
            
        },
        setUser: (userData) => {
            user.value = userData
        }, 
        handleLogin: async (users) => {
            loading.value = true
            try {
                const res = await api.logUser(users)
                if(res.data.success) {
                    //user.value = res.data.user
                    actions.value.setUser(res.data.user)
                    successMsg.value = res.data.msg 
                    const redirectPath = route.query.redirect || '/'
                    router.push(redirectPath)
                    setTimeout(() => {
                        successMsg.value = false
                    }, 3000)
                } else {
                    console.log(res.data)
                }
            } catch (error) {
                console.dir(error, {depth: null})
                //console.log(error)
                //errorMsg.value = res.data.error
            } finally {
                loading.value = false
            }
        },
        logout: async (users) => {
            loading.value = true
            try {
                const res = await api.logUser(users)
                if(res.data.success) {
                    user.value = null
                    successMsg.value = res.data.msg 
                    setTimeout(() => {
                        successMsg.value = false
                    }, 4000)
                } else {
                    console.log(res.data)
                }
            } catch (error) {
                console.dir(error, {depth: null})
            } finally {
                loading.value = false
                user.value = null
            }  
        }
    })

    

    function logout() {
        user.value = null

        router.push({
            name: 'home'
        })
    }

    return {
        user, errorMsg, loading, getters, actions, successMsg, rules,
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