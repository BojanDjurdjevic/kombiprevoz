import axios from "./config";

export default {
    getCountries(data) {
        console.log(data)
        return axios.get("", {params: {data}}, {
            headers: {
                "Content-type": "json/application",
                "Content-type": "multipart/form-data"   
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
        
    }
}