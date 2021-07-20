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
 *   label = @Translation("Dropdown (inside form)"),
 *   description = @Translation("A configurable widget that shows a dropdown as a form element."),
 * )
 */
class DropdownWidget extends ArrayWidget implements FacetsFormWidgetInterface {

  use FacetsFormWidgetTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'default_option_label' => 'Choose',
      'child_prefix' => '-',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet): array {
    return [
      'default_option_label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Default option label'),
        '#default_value' => $this->getConfiguration()['default_option_label'],
      ],
      'child_items_prefix' => [
        '#type' => 'textfield',
        '#title' => $this->t('Child prefix'),
        '#description' => $this->t('A prefix to be displayed for each child. Note, that the prefix will be displayed multiple times depending on the nesting level.'),
        '#default_value' => $this->getConfiguration()['child_prefix'],
      ]
    ] + parent::buildConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $items = parent::build($facet)[$facet->getFieldIdentifier()] ?? [];

    $options = $default_value = [];
    if ($facet->getShowOnlyOneResult()) {
      $options[NULL] = $this->getConfiguration()['default_option_label'];
    }

    $this->buildOptionsAndDefaultValue($options, $default_value, $items);

    return [
      $facet->id() => [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $default_value,
        '#multiple' => !$facet->getShowOnlyOneResult(),
        '#disabled' => empty($items),
      ],
    ];
  }

  protected function buildOptionsAndDefaultValue(array &$options, array &$default_value, array $items, int $depth = 0): void {
    foreach ($items as $item) {
      if (!empty($items['children'])) dpm($items['children']);
      $text = $item['values']['value'];
      if ($this->showNumbers) {
        $text .= " ({$item['values']['count']})";
      }
      // @todo Handle hierarchy/children.
      $options[$item['raw_value']] = $text;

      // Collect default values.
      if (!empty($item['values']['active'])) {
        $default_value[] = $item['raw_value'];
      }
    }
  }

}
