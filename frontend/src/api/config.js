import { useUserStore } from '@/stores/user';
import axios from 'axios';

const instance = axios.create({
    baseURL: "http://localhost:8080/",
    withCredentials: true
})

instance.interceptors.request.use(config => {
  const user = useUserStore()
  user.loading = true
  return config
}, error => {
  const user = useUserStore()
  user.loading = false
  return Promise.reject(error)
})

// Interceptor posle dobijenog odgovora
instance.interceptors.response.use(response => {
  const user = useUserStore()
  user.loading = false
  return response
}, error => {
  const user = useUserStore()
  user.loading = false
  return Promise.reject(error)
})

export default instance