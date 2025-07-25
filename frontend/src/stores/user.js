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

    const profile = ref({
        //user: true,
        users: {
            id: null,
            name: null,
            email: null,
            city: null,
            address: null,
            phone: null
        },
        updateProfile: true
    }) 
    
    if(user.value) {
        profile.value.users.id = user.id,
        profile.value.users.name = user.name,
        profile.value.users.email = user.email,
        profile.value.users.address = user.address,
        profile.value.users.phone = user.phone
        profile.value.users.city = user.city
    }
     

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
        },
        validStr: (value) => {
            if(value.length < 4) return 'Neadekvatna adresa. Mora imati najmanje 4 karaktera (samo slova i broj).'
            let forbiden = ["=", ")", "(", "+", "-", "*", "/", "|", "!", "<", ">"]
            let sum = 0
            forbiden.forEach(char => {
                if(value.includes(char)) sum++
            })
            if(sum) return 'Nevalidna adresa. Može sadržati samo slova i brojeve!'
        }
    }

    const errorMsg = ref(false)
    const successMsg = ref(false)
    const loading = ref(false)
    const profileDialog = ref(false)

    function showErr(error, time) {
        errorMsg.value = error.response.data.error
        setTimeout(() => {
            errorMsg.value = false
        }, time)
    }
    function showSucc(res, time) {
        successMsg.value = res.data.msg
        setTimeout(() => {
            successMsg.value = false
        }, time)
    }

    function clearMsg(time) {
        setTimeout(() => {
            successMsg.value = false
            errorMsg.value = false
        }, time)
    }

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
                    return true
                } else {
                    user.value = null
                    return true
                }
            } catch (error) {
                console.log(error)
                if(error.response.data.error) {
                    successMsg.value = "Dobrodošli na sajt Kombiprevoz!"
                    user.value = null
                    setTimeout(() => {
                        successMsg.value = false
                    }, 1000)
                }               
                return false
                
            } finally {
                loading.value = false
            }
            
        },
        setUser: (userData) => {
            user.value = userData
        }, 
        handleSignin: async (users) => {
            loading.value = true
            try {
                const res = await api.logUser(users)
                if(res.data.success) {
                    //user.value = res.data.user
                    actions.value.setUser(res.data.user)
                    //successMsg.value = res.data.msg 
                    const redirectPath = route.query.redirect || '/'
                    router.push(redirectPath)
                    showSucc(res, 6000)
                } else {
                    console.log(res.data)
                }
            } catch (error) {
                console.dir(error, {depth: null})
                if(error.response.data.error) {
                    showErr(error, 9000)
                    if(error.response.data.status === 500) {
                        router.push('/login')
                    }
                } else {
                    console.log('pogrešno dohvatanje')
                }
            } finally {
                loading.value = false
            }
        },
        handleLogin: async (users) => {
            loading.value = true
            try {
                const res = await api.logUser(users)
                if(res.data.success) {
                    //user.value = res.data.user
                    actions.value.setUser(res.data.user)
                    //successMsg.value = res.data.msg 
                    const redirectPath = route.query.redirect || '/'
                    router.push(redirectPath)
                    showSucc(res, 6000)
                } else {
                    console.log(res.data)
                }
            } catch (error) {
                console.dir(error, {depth: null})
                if(error.response.data.error) {
                    showErr(error, 9000)
                } else {
                    console.log('pogrešno dohvatanje')
                }
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
                    showSucc(res, 4000)
                } else {
                    console.log(res.data)
                }
            } catch (error) {
                console.dir(error, {depth: null})
            } finally {
                loading.value = false
                user.value = null
            }  
        },
        requestPassReset: async (users) => {
            loading.value = true
            try {
                const res = await api.requestReset(users)
                console.log(res.data)
                if(res.data.success) {
                    showSucc(res, 9000)
                }
            } catch (error) {
                console.dir(error)
                showErr(error, 6000)
            } finally {
                loading.value = false
            }
        },
        sendToken: async (token) => {
            loading.value = true
            try {
                const res = await api.requestReset(token)
                if(res.data.success) {
                    showSucc(res, 9000)
                    router.push('/login')
                }
            } catch (error) {
                console.dir(error)
                showErr(error, 9000)
                router.push('/request-password-reset')
            } finally {
                loading.value = false
            }
        },
        profileUpdate: async (users) => {
            loading.value = true
            try {
                const res = await api.requestReset(users)
                console.log(res.data)
                if(res.data.success) {
                    showSucc(res, 9000)
                    user.value = res.data.user
                    console.log(user.value)
                }
            } catch (error) {
                console.dir(error)
            } finally {
                loading.value = false
            }
        },
    })

    

    function logout() {
        user.value = null

        router.push({
            name: 'home'
        })
    }

    return {
        user, errorMsg, loading, getters, actions, successMsg, rules, profileDialog, profile,
        logout, showErr, showSucc, clearMsg,
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

    KasacPrasac123!
 */