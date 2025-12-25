import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import MyBookings from '@/views/MyBookingsView.vue'
import Destinations from '@/views/DestinationsView.vue'
import SearchResult from '@/views/SearchResultView.vue'
import CitiesView from '@/views/CitiesView.vue'
import CityBook from '@/views/CityBookView.vue'
import BookNow from '@/views/BookNowView.vue'
import ManageBooking from '@/views/ManageBookingView.vue'
import Contact from '@/views/ContactView.vue'
import SignInView from '@/views/SignInView.vue'
import LoginView from '@/views/LoginView.vue'
import ResetPassView from '@/views/ResetPassView.vue'
import { useUserStore } from '@/stores/user'
import RequestPassResetView from '@/views/RequestPassResetView.vue'
import MyProfileView from '@/views/MyProfileView.vue'
import AdminDashView from '@/views/AdminDashView.vue'
import DisclaimerView from '@/views/DisclaimerView.vue'

const router = createRouter({
  
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      //component: () => import('../views/HomeView.vue'),
      meta: {
        title: 'Kombi transfer putnika u inostranstvo | Od vrata do vrata',
        description: 'Pouzdan i udoban kombi transfer putnika iz Srbije ka Evropi. Prevoz od vrata do vrata, profesionalni vozači.'
      }
    },
    {
      path: '/kontakt',
      name: 'kontakt',
      component: Contact,
      meta: {
        title: 'Kontakt | Kombi transfer putnika',
        description: 'Kontaktirajte nas za sve informacije o kombi prevozu putnika u inostranstvo.'
      }
    },
    {
      path: '/rezervacije',
      name: 'rezervacije',
      component: MyBookings,
      meta: {
        requireAuth: true
      }
    },
    {
      path: '/destinacije',
      name: 'destinacije',
      component: Destinations,
      meta: {
        title: 'Destinacije | Kombi transfer putnika',
        description: 'Pregled svih dostupnih destinacija za kombi prevoz putnika iz Srbije ka inostranstvu.'
      }
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
      meta: {
        title: 'Gradovi | Kombi transfer putnika',
        description: 'Lista gradova i destinacija za kombi prevoz putnika. Izaberite grad polaska i dolaska.'
      }
    },
    {
      path: '/grad',
      name: 'grad',
      component: CityBook
    },
    {
      path: '/korpa',
      name: 'korpa',
      component: BookNow,
      meta: {
        requireAuth: true
      }
    },
    {
      path: '/uredi',
      name: 'uredi',
      component: ManageBooking,
    },
    {
      path: '/registracija',
      name: 'signin',
      component: SignInView,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/password-reset',
      name: 'password-reset',
      component: ResetPassView,
    },
    {
      path: '/request-password-reset',
      name: 'request-password-reset',
      component: RequestPassResetView,
    },
    {
      path: '/profil',
      name: 'profil',
      component: MyProfileView,
      meta: {
        requireAuth: true
      }
    },
    {
      path: '/disclaimer',
      name: 'disclaimer',
      component: DisclaimerView,
      meta: {
        noindex: true
      }
    },
    {
      path: '/admin',
      name: 'admin',
      component: AdminDashView, 
      meta: {
        requireAuth: true,
        requiresRole: ['Superadmin', 'Admin'],
        noindex: true
      },
      // pitanje da li je potrebno?
      children: [
        {
          path: 'chat',
          name: 'AdminChat',
          component: () => import('@/views/admin/AdminChatTickets.vue'),
          meta: { 
            requiresAuth: true, 
            requiresRole: true,
            title: 'Chat Tiketi'
          }
        }
      ] 
    },
  ],
})

router.beforeEach((to, from, next) => {
  const user = useUserStore()
  console.log(user)
  if(to.meta.requireAuth && !user.getters.isAuthenticated(user.user)) {
    user.errorMsg = "Niste ulogovani! Molimo da se ulogujete pre nego što pristupite ovoj stranici."
    setTimeout(() => {
      next({
        path: '/login',
        query: { redirect: to.fullPath }
      })
      user.errorMsg = null
    }, 1000);
    return
  }

  if (to.meta.requiresRole) {
     const allowedRoles = to.meta.requiresRole
     const userRole = user.user?.status

     if (!allowedRoles.includes(userRole)) {
       user.errorMsg = "Nemate dozvolu."
       setTimeout(() => {
         next({ path: '/' })
         user.errorMsg = null
       }, 1000)
       return
     }
  }

  next()
})

router.afterEach((to) => {
  // TITLE
  document.title = to.meta.title || 'Kombi Transfer Putnika'

  // DESCRIPTION
  let description = document.querySelector('meta[name="description"]')
  if (!description) {
    description = document.createElement('meta')
    description.setAttribute('name', 'description')
    document.head.appendChild(description)
  }

  description.setAttribute(
    'content',
    to.meta.description || 'Pouzdan kombi prevoz putnika iz Srbije ka Evropi.'
  )

  // NOINDEX za admin
  let robots = document.querySelector('meta[name="robots"]')
  if (!robots) {
    robots = document.createElement('meta')
    robots.setAttribute('name', 'robots')
    document.head.appendChild(robots)
  }

  if (to.meta.noindex) {
    robots.setAttribute('content', 'noindex, nofollow')
  } else {
    robots.setAttribute('content', 'index, follow')
  }
})


export default router
