import axios from "./config";

export default {

    //---------------------------- COUNTRIES ---------------------//

    getCountries(data) {
        console.log(data)
        return axios.get("", {params: {data}})
        
    },
    insertCountry(formData) {
        console.log(formData)
        return axios.post("", formData, {
            headers: {
                "Content-type": "multipart/form-data"
            },
            withCredentials: true
        })
        
    },
    updateCountry(formData) {
        console.log(formData)
        return axios.put("", formData, {
            headers: {
                "Content-type": "multipart/form-data"
            },
            withCredentials: true
        })
        
    },
    deleteCountry(data) {
        console.log(data)
        return axios.delete("", {data}, {
            headers: {
                "Content-type": "json/application"
            }
        })
        
    },

    //---------------------------- CITIES ---------------------//

    getCities(cities) {
        console.log(cities)
        return axios.get("", {params: {cities}}, {
            headers: {
                "Content-type": "json/application"   
            }
        })
        
    },
    insertCity(formData) {
        console.log(formData)
        return axios.post("", formData, {
            headers: {
                "Content-type": "multipart/form-data"
            },
            withCredentials: true
        })
    },
    updateCity(data) {
        return axios.put("", data, {
            headers: {
                "Content-type": "json/application"
            },
            withCredentials: true
        })
    },

    //---------------------------- TOURS ---------------------//

    getTours(tours) {
        return axios.get("", {params: {tours}})
    },
    
    checkAvailableDates(tours) {
        return axios.get("", {params: {tours}})
    },

    createTour(tour) {
        //return console.log('Novi Tour :', tour)
        return axios.post("", tour)
    },
    updateTour(tour) {
        //return console.log(tour)
        return axios.put("", tour)
    },
    deleteTour(tours) {
        //return console.log(tours)
        return axios.delete("", {data: tours}, {
            headers: {
                "Content-type": "application/json"
            }
        })
    },

    //------------------------------- USER -----------------------//

    getUser(users) {
        console.log(users)
        return axios.get("", {params: {users}}, {
            headers: {
                "Content-type": "json/application"   
            }
        }) 
    },
    getUsrLogs(users) {
        console.log(users)
        return axios.get("", users)
    },
    isLogged(user) {
        return axios.post("", user)
    },
    logUser(users) {
        return axios.post("", users)
    }, /* // Realno mi ne treba
    logout(users) {
        return axios.post("", users)
    } */
   requestReset(users) {
        return axios.put("", users)
   },

   //----------------------------------- ORDER --------------------------//

   getOrder(orders) {
        //return orders
        return axios.get("", {params: {orders}})
   },  

   getAdminOrder(adminOrders) {
        //return orders
        return axios.get("", {params: {adminOrders}})
   },  

   makeOrder(tour) {
    //return console.log(tour)
        return axios.post("", tour)
   },
   orderItemUpdate(order) {
        //
        return axios.put("", order)
   },
   orderItemDelete(orders) {
        return axios.delete("", {data: orders}, {
            headers: {
                "Content-type": "application/json"
            }
        })
   },

   //--------------------------------- CHAT ---------------------------------//

   getChat(config) {
        config.silent = true
        return axios.get("", config)
   },
   sendChat(data) {
    console.log('API sendChat called with:', data);
    try {
        const response = axios.post("", data, { silent: true });
        console.log('Axios response:', response);
        return response;
    } catch (error) {
        console.error('Axios error in sendChat:', error);
        throw error;
    }
    //return axios.post("", data)
   }
}