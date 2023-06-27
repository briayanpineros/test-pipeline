<?php

namespace Drupal\conil_saved_searches\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Saved Searches entities.
 *
 * @ingroup conil_saved_searches
 */
class SavedSearchesDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->getEntity()->delete();
    $url = Url::fromRoute('view.search_history.page_1');
    return new RedirectResponse($url->toString());
  }

}
