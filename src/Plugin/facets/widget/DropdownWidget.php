<?php

namespace Drupal\facets_form\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\widget\ArrayWidget;
use Drupal\facets\Plugin\facets\widget\DropdownWidget as FacetsDropdownWidget;
use Drupal\facets_form\FacetsFormWidgetInterface;
use Drupal\facets_form\FacetsFormWidgetTrait;

/**
 * Form element alternative to 'dropdown' widget.
 *
 * @FacetsWidget(
 *   id = "facets_form_dropdown",
 *   label = @Translation("Dropdown (as form element)"),
 *   description = @Translation("A configurable widget that shows a dropdown as form element."),
 * )
 */
class DropdownWidget extends ArrayWidget implements FacetsFormWidgetInterface {

  use FacetsFormWidgetTrait;

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $field_id = $facet->getFieldIdentifier();
    $items = parent::build($facet)[$field_id];

    $options = [];
    foreach ($items as $item) {
      $text = $item['values']['value'];
      if ($this->showNumbers) {
        $text .= " ({$item['values']['count']})";
      }
      // @todo Handle hierarchy/children.
      $options[$item['raw_value']] = $text;
    }
    return [
      $facet->id() => [
        '#type' => 'select',
        '#options' => $options,
      ],
    ];
  }

}
