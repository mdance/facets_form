<?php

namespace Drupal\facets_form\Plugin\Block\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class FacetsFormBlockDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // @todo Start: Inject.
    $manager = \Drupal::getContainer()->get('plugin.manager.facets.facet_source');
    // @todo End: Inject.

    foreach ($manager->getDefinitions() as $plugin_id => $plugin_definition) {
      $definition = $base_plugin_definition;
      $definition['admin_label'] = $this->t('Facet form: @source', [
        '@source' => $plugin_definition['label'],
      ]);
      $this->derivatives[$plugin_id] = $definition;
    }
    return $this->derivatives;
  }

}
