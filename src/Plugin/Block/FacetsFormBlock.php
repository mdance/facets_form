<?php

namespace Drupal\facets_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\facets_form\Form\FacetsForm;

/**
 * @Block(
 *  id = "facets_form",
 *  admin_label = @Translation("Facets form"),
 *  category = @Translation("Facets"),
 *  deriver = "Drupal\facets_form\Plugin\Block\Deriver\FacetsFormBlockDeriver"
 * )
 */
class FacetsFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // @todo Start: Inject
    $entity_type_manager = \Drupal::entityTypeManager();
    $form_builder = \Drupal::formBuilder();
    // @todo End: Inject

    return $form_builder->getForm(FacetsForm::class, $this->getDerivativeId());
  }

}
