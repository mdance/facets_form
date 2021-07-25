<?php

namespace Drupal\facets_form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;

/**
 * Reusable code for widget plugins eligible for facets forms.
 */
trait FacetsFormWidgetTrait {

  /**
   * {@inheritdoc}
   */
  public function prepareValueForUrl(FacetInterface $facet, array &$form, FormStateInterface $form_state): array {
    $value = $form_state->getValue($facet->id());
    if (!$value) {
      return [];
    }
    return is_array($value) ? array_values($value) : [$value];
  }

}
