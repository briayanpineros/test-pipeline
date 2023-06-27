<?php

namespace Drupal\conil_webpush\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GuardarTokenController extends ControllerBase {

  public function guardarToken(Request $request) {
    $token = $request->request->get('token');
    if (!$token) {
      return new Response('Token no recibido.', 400);
    }

    // Verificar si el token ya existe en la base de datos.
    $connection = Drupal::database();
    $query = $connection->select('web_push_token', 'wpt')
      ->fields('wpt', ['id'])
      ->condition('wpt.token', $token);
    $result = $query->execute();
    $exists = $result->fetchAssoc();

    // Si el token no existe en la base de datos, almacenarlo.
    if (!$exists) {
      $query = $connection->insert('web_push_token')
        ->fields([
          'token' => $token,
        ]);
      $query->execute();
    }

    // Enviar la notificación push utilizando Firebase.
    $this->enviarNotificacionBienvenida($token);

    return new Response('Token almacenado y notificación enviada.');
  }


  private function enviarNotificacionBienvenida($token) {
    $fakeToken = $token;
    $messageService = Drupal::service('firebase.message');
    $messageService->setRecipients($fakeToken);
    $messageService->setNotification([
      'title' => 'Conil de la Frontera',
      'body' => $this->t('¡Bienvenido al Portal Turístico de Conil de la Frontera!'), //¡Bienvenido al Portal Turístico de Conil de la Frontera!
      'badge' => 1,
      'icon' => 'conil-icon.png',
    ]);
    $messageService->setData([
      'score' => '3x1',
      'date' => date('Y-m-d'),
      'optional' => 'Data is used to send silent pushes. Otherwise, optional.',
    ]);
    $messageService->setOptions(['priority' => 'normal']);
    $messageService->send();
  }

}

