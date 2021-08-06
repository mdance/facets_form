<?php

declare(strict_types = 1);

namespace Drupal\facets_form\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\widget\ArrayWidget;
use Drupal\facets_form\FacetsFormWidgetInterface;
use Drupal\facets_form\FacetsFormWidgetTrait;

/**
 * Form element alternative to 'checkbox' widget.
 *
 * @FacetsWidget(
 *   id = "facets_form_checkbox",
 *   label = @Translation("Checkboxes (inside form)"),
 *   description = @Translation("A configurable widget that shows checkboxes as a form element."),
 * )
 */
class CheckboxWidget extends ArrayWidget implements FacetsFormWidgetInterface {

  use FacetsFormWidgetTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'disabled_on_empty' => FALSE,
      'indent_class' => 'indented',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet): array {
    return [
      'disabled_on_empty' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Disable when there are no results'),
        '#default_value' => $this->getConfiguration()['disabled_on_empty'],
      ],
      'indent_class' => [
        '#type' => 'textfield',
        '#title' => $this->t('Class used for indentation'),
        '#default_value' => $this->getConfiguration()['indent_class'],
      ],
    ] + parent::buildConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet): array {
    $items = parent::build($facet)[$facet->getFieldIdentifier()] ?? [];

    $this->processItems($items);

    $facet_id = $facet->id();
    $build[$facet_id] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $facet->getName(),
      '#title_display' => $facet->get('show_title') ? 'before' : 'invisible',
    ];
    foreach ($this->processedItems as $value => $data) {
      $build[$facet_id][$value] = [
        // @todo Honour 'Ensure that only one result can be displayed' config.
        // @see https://www.drupal.org/project/facets_form/issues/3227076
        '#type' => 'checkbox',
        '#title' => $data['label'],
        '#default_value' => $data['default'],
        '#disabled' => $this->getConfiguration()['disabled_on_empty'] && empty($items),
        '#prefix' => str_repeat('<div class="' . $this->getConfiguration()['indent_class'] . '">', $data['depth']),
        '#suffix' => str_repeat('</div>', $data['depth']),
      ];
    };

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptionLabel(array $item, int $depth) {
    $text = $item['values']['value'];
    if ($this->getConfiguration()['show_numbers']) {
      $text .= " ({$item['values']['count']})";
    }
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareValueForUrl(FacetInterface $facet, array &$form, FormStateInterface $form_state): array {
    return array_keys(array_filter($form_state->getValue($facet->id())));
  }

}
