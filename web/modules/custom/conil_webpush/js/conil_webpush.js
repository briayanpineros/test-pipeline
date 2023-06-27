(function ($, Drupal, firebase, drupalSettings) {

  Drupal.behaviors.firebase = {
    attach: function (context, settings) {

      function sendTokenToServer(token) {
        $.ajax({
          url: '/guardar-token',
          type: 'POST',
          data: { token: token },
          success: function (data) {
          },
          error: function (error) {
          }
        });
      }

      // Configura Firebase con tus credenciales
      let config = {
        apiKey: drupalSettings.webpush_config.apiKey,
        authDomain: drupalSettings.webpush_config.authDomain,
        projectId: drupalSettings.webpush_config.projectId,
        storageBucket: drupalSettings.webpush_config.storageBucket,
        messagingSenderId: drupalSettings.webpush_config.messagingSenderId,
        appId: drupalSettings.webpush_config.appId,
        measurementId: drupalSettings.webpush_config.measurementId
      };

      if (firebase.apps.length === 0) {
        firebase.initializeApp(config);
      }

        // Inicializa el servicio de mensajería de Firebase
        const messaging = firebase.messaging();

        // Registra el Service Worker para recibir notificaciones push

      if ('serviceWorker' in navigator) {
        // Verificar si el Service Worker ya ha sido registrado
        if (!localStorage.getItem('serviceWorkerRegistered')) {
          // Registrar el Service Worker
          navigator.serviceWorker.register('firebase-messaging-sw.js')
            .then(registration => {
              // Guardar la bandera en almacenamiento local
              localStorage.setItem('serviceWorkerRegistered', true);
            })
            .catch(error => {
            });
        }
      }

       if (!localStorage.getItem('tokenAlreadySent')) {
        // Solicita el permiso del usuario para recibir notificaciones push
        messaging.requestPermission().then(() => {
          // Obtiene el token de registro del usuario
          messaging.getToken().then((token) => {
            sendTokenToServer(token);
            localStorage.setItem('tokenAlreadySent', true);
          });
          firebase.no
        }).catch((err) => {
        });
       }

    }
  };

})(jQuery, Drupal, firebase, drupalSettings);