import axios from "./config";

export default {

    //---------------------------- COUNTRIES ---------------------//

    getCountries(data) {
        console.log(data)
        return axios.get("", {params: {data}}, {
            headers: {
                "Content-type": "json/application"   
            }
        })
        
    },
    insertCountry(data) {
        console.log(data)
        return axios.post("", data, {
            headers: {
                "Content-type": "json/application"
            }
        })
        
    },
    updateCountry(data) {
        console.log(data)
        return axios.put("", data, {
            headers: {
                "Content-type": "json/application"
            }
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

    //---------------------------- TOURS ---------------------//

    getTours(tours) {
        return axios.get("", {params: {tours}})
    },
    
    checkAvailableDates(tours) {
        return axios.get("", {params: {tours}})
    }
}