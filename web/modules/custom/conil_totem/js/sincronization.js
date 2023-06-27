var URLdomain = window.location.hostname;
const identifier = URLdomain.split('.')[0];
var timeoutID;
var video = document.createElement('video');
video.id = "player";
video.muted = true;
video.autoplay = true;
var body = document.querySelector("body");
body.appendChild(video);
var previous_media = [];

if(identifier != 'turismoconil') {
  document.addEventListener("DOMContentLoaded", function(event) {
    const api = fetch('api').then(response => response.text());
    function screensaver_create(){
      // Creamos un nuevo XMLHttpRequest
      var xhttp = new XMLHttpRequest();
      var mediaTotem = [];

      xhttp.onreadystatechange = function() {
        if (xhttp.readyState === XMLHttpRequest.DONE) {
          const status = xhttp.status;
          if (status === 0 || (status >= 200 && status < 400)) {
            var conf = JSON.parse(this.responseText);
            // Configuración por defecto
            var nextMedia = conf['default']['nextMedia'];
            //var waitTime = conf['default']['waitTime'];
            for (var i = 0; i < conf['default']['media_content'].length; i++) {
              mediaTotem.unshift(conf['default']['media_content'][i]);
            }
            var today = new Date();
            var current_date = new Date(today.getFullYear() + '-' + (today.getMonth()+1) + '-' + today.getDate());

            Object.keys(conf['totems_list']).forEach(key => totem = conf['totems_list'][key]);
            for(var x = 0; x < totem['dates'].length; x++){
              var start_time = new Date(totem['dates'][x]['start_time']);
              var end_time = new Date(totem['dates'][x]['end_time']);
              if(current_date >= start_time && current_date <= end_time) {
                hour = today.getHours() * 3600 + today.getMinutes() * 60;
                for(var y =0; y < totem['dates'][x]['content'].length; y++){
                  var start = totem['dates'][x]['content'][y]['start'];
                  var end = totem['dates'][x]['content'][y]['end'];
                  if (hour >= start && hour <= end) {
                    mediaTotem = [];
                    for(var z = 0; z < totem['dates'][x]['content'][y]['media'].length; z++){
                      mediaTotem.unshift(totem['dates'][x]['content'][y]['media'][z]);
                    }
                  }
                }
              }
            }
          }
        }
        if ((previous_media.toString() != mediaTotem.toString()) && mediaTotem.length != 0) {
          clearTimeout(timeoutID);
          previous_media = mediaTotem;
          screensaver(mediaTotem, nextMedia);
        }

      };

      // Endpoint de la API y método que se va a usar para llamar
      xhttp.open("GET", "https://conil.ddev.site/services/totem-configuration/" + identifier + "?api-key=api", true);
      xhttp.send(null);

      function screensaver(mediaTotem, nextMedia) {
        // Variables
        const interval_time = nextMedia * 1000;
        let actualPosition = 0;

        /**
         * Funcion que cambia el media a la siguiente posicion
         */
        function forwardMedia() {
            if(actualPosition >= mediaTotem.length - 1) {
                actualPosition = 0;
            } else {
                actualPosition++;
            }
            renderMedia();
        }

        /**
         * Funcion que actualiza el media dependiendo de actualPosition
         */
        async function renderMedia () {
          if(mediaTotem[actualPosition].substring(mediaTotem[actualPosition].length-3) == "mp4"){
            clearTimeout(timeoutID);
            var children = document.querySelector("body").children;
            for(var i = 0; i < children.length; i++) {
              if(document.querySelector("body").children[i].localName != 'video'){
                document.querySelector("body").children[i].style.display = "none";
              }
            }
            video.setAttribute("src",mediaTotem[actualPosition]);
            video.load();
            video.style.visibility = "visible";
            body.style.background = "black";
            await video.play();
            var duracion = document.querySelector("video");
            timeoutID = setTimeout(forwardMedia, (duracion.duration * 1000));

          }
          else {
            clearTimeout(timeoutID);
            var children = document.querySelector("body").children;
            for(var i = 0; i < children.length; i++) {
              if(document.querySelector("body").children[i].localName != 'video'){
                document.querySelector("body").children[i].style.display = "none";
              }
            }
            video.pause;
            video.style.visibility = "hidden";
            document.getElementsByTagName("body")[0].style.background = "url(" + mediaTotem[actualPosition] + ")";
            timeoutID = setTimeout(forwardMedia, interval_time);
          }
        }
        // Iniciar
        renderMedia();
      }
    }
    screensaver_create();
    // Llama al servicio y crea el array con el contenido media
    setInterval(screensaver_create, (300 * 1000));
  });
}
