import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import MyBookings from '@/views/MyBookings.vue'
import Destinations from '@/views/Destinations.vue'
import SearchResult from '@/views/SearchResult.vue'
import CitiesView from '@/views/CitiesView.vue'
import CityBook from '@/views/CityBook.vue'
import BookNow from '@/views/BookNow.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/about',
      name: 'about',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/AboutView.vue'),
    },
    {
      path: '/rezervacije',
      name: 'rezervacije',
      component: MyBookings,
    },
    {
      path: '/destinacije',
      name: 'destinacije',
      component: Destinations,
    },
    {
      path: '/rezultati',
      name: 'rezultati',
      component: SearchResult,
    },
    {
      path: '/gradovi',
      name: 'gradovi',
      component: CitiesView,
    },
    {
      path: '/grad',
      name: 'grad',
      component: CityBook,
    },
    {
      path: '/korpa',
      name: 'korpa',
      component: BookNow,
    },
  ],
})

export default router
