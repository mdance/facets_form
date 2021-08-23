<?php

declare(strict_types = 1);

namespace Drupal\facets_form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;

/**
 * Reusable code for widget plugins eligible for facets forms.
 */
trait FacetsFormWidgetTrait {

  /**
   * Static cache of processed items.
   *
   * @var array[]
   */
  protected $processedItems;

  /**
   * {@inheritdoc}
   */
  public function prepareValueForUrl(FacetInterface $facet, array &$form, FormStateInterface $form_state): array {
    $values = $form_state->getValue($facet->id());

    if (!$values) {
      return [];
    }

    if (!is_array($values)) {
      return [$values];
    }

    $result = [];
    foreach ($values as $key => $value) {
      if ($value !== 0) {
        $result[] = $key;
      }
    }

    return $result;
  }

  /**
   * Builds and returns the option label/text.
   *
   * @param array $item
   *   The facet item.
   * @param int $depth
   *   The item depth.
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet entity.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The item label.
   */
  abstract protected function getOptionLabel(array $item, int $depth, FacetInterface $facet);

  /**
   * Processes and statically cache the list of items.
   *
   * @param array $items
   *   The list of items.
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet entity.
   */
  protected function processItems(array $items, FacetInterface $facet): void {
    if (!isset($this->processedItems)) {
      $this->processedItems = [];
      $this->doProcessItems($items, $facet);
    }
  }

  /**
   * Processes a list of items.
   *
   * @param array $items
   *   The list of items.
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet entity.
   * @param int $depth
   *   (optional) The "zero based" depth of the current items. Used internally.
   */
  protected function doProcessItems(array $items, FacetInterface $facet, int $depth = -1): void {
    $depth++;
    foreach ($items as $item) {
      $this->processedItems[$item['raw_value']] = [
        'label' => $this->getOptionLabel($item, $depth, $facet),
        'default' => !empty($item['values']['active']),
        'depth' => $depth,
      ];
      if (!empty($item['children'])) {
        $this->doProcessItems($item['children'][0], $facet, $depth);
      }
    }
  }

}
