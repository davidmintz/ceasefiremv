import axios from 'axios';
console.log( `axios is a ${typeof axios}`   );
console.log("Hello and WTF from consent.js");

let button = document.getElementById("consent-button");
button.addEventListener("click",
function(){
    console.log("shit is real in our 'click' event listener");
    axios.head('/privacy-consent')
        .then(function(response){
            console.log(response)
            button.setAttribute("hidden","hidden")
            document.getElementById("consent-thank-you").removeAttribute("hidden");
    });
})

