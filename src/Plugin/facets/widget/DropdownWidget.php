<?php

declare(strict_types = 1);

namespace Drupal\facets_form\Plugin\facets\widget;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\widget\ArrayWidget;
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
   * Show the amount of results next to the result.
   *
   * @var bool
   */
  protected $showNumbers;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'default_option_label' => 'Choose',
      'child_items_prefix' => '-',
      'disabled_on_empty' => FALSE,
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
        '#title' => $this->t('Child prefix character'),
        '#description' => $this->t('A prefix to be displayed for each child. Note, that the prefix will be displayed multiple times depending on the nesting level.'),
        '#default_value' => $this->getConfiguration()['child_items_prefix'],
        '#maxlength' => 1,
      ],
      'disabled_on_empty' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Disable when there are no results'),
        '#default_value' => $this->getConfiguration()['disabled_on_empty'],
      ],
    ] + parent::buildConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $items = parent::build($facet)[$facet->getFieldIdentifier()] ?? [];

    $this->processItems($items);

    $options = [];
    if ($facet->getShowOnlyOneResult()) {
      $options[NULL] = $this->getConfiguration()['default_option_label'];
    }
    $options += array_map(function (array $item) {
      return $item['label'];
    }, $this->processedItems);

    return [
      $facet->id() => [
        '#type' => 'select',
        '#title' => $facet->getName(),
        '#title_display' => $facet->get('show_title') ? 'before' : 'invisible',
        '#options' => $options,
        '#default_value' => array_keys(array_filter($this->processedItems, function (array $item): bool {
          return $item['default'];
        })),
        '#multiple' => !$facet->getShowOnlyOneResult(),
        '#disabled' => $this->getConfiguration()['disabled_on_empty'] && empty($items),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptionLabel(array $item, int $depth) {
    // @todo Allow customizing the label in #3226866.
    // @see https://www.drupal.org/project/facets_form/issues/3226866
    $text = $item['values']['value'];
    if ($this->getConfiguration()['show_numbers']) {
      $text .= " ({$item['values']['count']})";
    }

    // Indent child items if a prefix has been set.
    $pattern = '@text';
    if ($depth > 0 && $indent_char = $this->getConfiguration()['child_items_prefix']) {
      // Standard HTML <option> element is trimming leading spaces.
      $indent_char = $indent_char !== ' ' ? $indent_char : '&nbsp;';
      $pattern = str_repeat($indent_char, $depth) . " {$pattern}";
    }

    return new FormattableMarkup($pattern, ['@text' => $text]);
  }

}
