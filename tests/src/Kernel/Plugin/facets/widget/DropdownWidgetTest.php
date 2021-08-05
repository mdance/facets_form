<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form\Kernel\Plugin\facets\widget;

use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\Result;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\facets_form\Plugin\facets\widget\DropdownWidget
 * @group facets_form
 */
class DropdownWidgetTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'facets',
    'facets_form',
  ];

  /**
   * Test the Facets Form dropdown widget build.
   *
   * @param array $facet_values
   *   Facet entity values.
   * @param array $widget_config
   *   The widget configuration.
   * @param array $data
   *   The used to build the results.
   * @param array $active_items
   *   Active items.
   * @param array $expected_default_values
   *   Select '#default_value' expectation.
   * @param bool $expected_multiple
   *   Select element '#multiple' expectation.
   * @param bool $expected_disabled
   *   Select element '#disabled' expectation.
   * @param array $expected_options
   *   Select element '#options' expectation.
   *
   * @dataProvider providerTestPlugin
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  public function testPlugin(
    array $facet_values,
    array $widget_config,
    array $data,
    array $active_items,
    array $expected_default_values,
    bool $expected_multiple,
    bool $expected_disabled,
    array $expected_options
  ): void {
    $facet = new Facet(['id' => 'foo'] + $facet_values, 'facets_facet');
    $facet->setWidget('facets_form_dropdown', $widget_config);
    $facet->setResults($this->getResults($facet, $data, $active_items));

    $build = $facet->getWidgetInstance()->build($facet)['foo'];
    $this->assertSame('select', $build['#type']);
    $this->assertEquals($expected_default_values, $build['#default_value']);
    $this->assertSame($expected_multiple, $build['#multiple']);
    $this->assertSame($expected_disabled, $build['#disabled']);
    $this->assertEquals($expected_options, $build['#options']);
  }

  /**
   * Provides test cases for ::testPlugin().
   *
   * @return array[]
   *   Test cases.
   */
  public function providerTestPlugin(): array {
    return [
      'default' => [
        [],
        [],
        [
          '1' => [
            '1.1' => [
              '1.1.1' => [],
            ],
            '1.2' => [],
            '1.3' => [
              '1.3.1' => [],
            ],
          ],
          '2' => [
            '2.1' => [],
          ],
        ],
        ['1.1', '2'],
        ['1.1', '2'],
        TRUE,
        FALSE,
        [
          '1' => 'One',
          '1.1' => '- One.One',
          '1.1.1' => '-- One.One.One',
          '1.2' => '- One.Two',
          '1.3' => '- One.Three',
          '1.3.1' => '-- One.Three.One',
          '2' => 'Two',
          '2.1' => '- Two.One',
        ],
      ],
      'different_option_label' => [
        [
          'show_only_one_result' => TRUE,
        ],
        [
          'default_option_label' => 'Select',
        ],
        [
          '1' => [],
          '2' => [],
        ],
        ['1'],
        ['1'],
        FALSE,
        FALSE,
        [
          '' => 'Select',
          '1' => 'One',
          '2' => 'Two',
        ],
      ],
      'child_item_prefix_asterix' => [
        [],
        [
          'child_items_prefix' => '*',
        ],
        [
          '1' => [
            '1.1' => [
              '1.1.1' => [],
            ],
          ],
        ],
        ['1.1.1'],
        ['1.1.1'],
        TRUE,
        FALSE,
        [
          '1' => 'One',
          '1.1' => '* One.One',
          '1.1.1' => '** One.One.One',
        ],
      ],
      'child_item_prefix_space' => [
        [],
        [
          'child_items_prefix' => ' ',
        ],
        [
          '1' => [
            '1.1' => [
              '1.1.1' => [],
            ],
          ],
        ],
        [],
        [],
        TRUE,
        FALSE,
        [
          '1' => 'One',
          '1.1' => '&nbsp; One.One',
          '1.1.1' => '&nbsp;&nbsp; One.One.One',
        ],
      ],
      'empty_items' => [
        [],
        [
          'disabled_on_empty' => TRUE,
        ],
        [],
        [],
        [],
        TRUE,
        TRUE,
        [],
      ],
      'with_show_number' => [
        [],
        [
          'show_numbers' => TRUE,
        ],
        [
          '1' => [],
          '2' => [],
        ],
        ['1'],
        ['1'],
        TRUE,
        FALSE,
        [
          '1' => 'One (1)',
          '2' => 'Two (2)',
        ],
      ],
      'pick_one_with_hash' => [
        [
          'show_only_one_result' => TRUE,
        ],
        [
          'default_option_label' => 'Pick one',
          'child_items_prefix' => '#',
          'show_numbers' => TRUE,
        ],
        [
          '1' => [
            '1.1' => [
              '1.1.1' => [],
            ],
            '1.2' => [],
            '1.3' => [
              '1.3.1' => [],
            ],
          ],
          '2' => [
            '2.1' => [],
          ],
        ],
        ['1.1', '2'],
        ['1.1', '2'],
        FALSE,
        FALSE,
        [
          '' => 'Pick one',
          '1' => 'One (1)',
          '1.1' => '# One.One (11)',
          '1.1.1' => '## One.One.One (111)',
          '1.2' => '# One.Two (12)',
          '1.3' => '# One.Three (13)',
          '1.3.1' => '## One.Three.One (131)',
          '2' => 'Two (2)',
          '2.1' => '# Two.One (21)',
        ],
      ],
    ];
  }

  /**
   * Builds a list of deep nested results.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet.
   * @param array $data
   *   Result data.
   * @param array $active
   *   Active items.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   A list of nested results.
   */
  protected function getResults(FacetInterface $facet, array $data, array $active = []): array {
    $results = [];
    foreach ($data as $value => $children) {
      $display_value = str_replace(['1', '2', '3'], ['One', 'Two', 'Three'], $value);
      $count = (int) str_replace('.', '', $value);
      $result = new Result($facet, $value, $display_value, $count);
      $result->setUrl(Url::fromUri("http://example.com/{$value}"));
      if (in_array($value, $active)) {
        $result->setActiveState(TRUE);
      }
      if (!empty($children)) {
        $result->setChildren($this->getResults($facet, $children, $active));
      }
      $results[] = $result;
    }
    return $results;
  }

}