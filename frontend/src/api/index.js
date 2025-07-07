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
        
    }
}