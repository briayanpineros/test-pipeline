self.addEventListener('push', function (event) {
  if (event.data) {
    const title = event.data.json().notification.title;
    const options = {
      body: event.data.json().notification.body,
      icon: event.data.json().notification.icon,
      badge: event.data.json().notification.badge
    };
    event.waitUntil(self.registration.showNotification(title, options));
  }
});

// Abre una ventana al hacer clic en la notificación
self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  //event.waitUntil(clients.openWindow(event.notification.click_action));
});