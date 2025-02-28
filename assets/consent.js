import axios from 'axios';



let button = document.getElementById("consent-button");
button.addEventListener("click",
function(){
    //console.log("shit is real in our 'click' event listener");
    axios.head('/privacy-consent')
        .then(function(response){
            console.log(response);
            button.setAttribute("hidden","hidden");
            document.getElementById("consent-thank-you").removeAttribute("hidden");
    });
})

