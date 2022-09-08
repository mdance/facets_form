<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form_date_range\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets_form\Traits\FacetUrlTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Test the facets form with date range widget.
 *
 * @group facets_form
 */
class DateRangeIntegrationTest extends BrowserTestBase {

  use BlockTestTrait;
  use ContentTypeCreationTrait;
  use FacetUrlTestTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'facets_summary',
    'facets_form_date_range',
    'facets_form_search_api_dependency',
    'node',
    'search_api_test_db',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'bundle_1', 'name' => 'Bundle 1']);
    $date = new DrupalDateTime('15-08-2021');
    $this->createNode([
      'title' => 'Llama',
      'type' => 'bundle_1',
      'created' => $date->getTimestamp(),
    ]);
    $date = new DrupalDateTime('17-08-2021');
    $this->createNode([
      'title' => 'Emu',
      'type' => 'bundle_1',
      'created' => $date->getTimestamp(),
    ]);

    /** @var \Drupal\search_api\Entity\Index $index */
    $index = Index::load('test');
    $this->assertSame(2, $index->indexItems());
  }

  /**
   * Test the facets form with date range widget.
   */
  public function testDateRangeDateOnly(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    FacetsSummary::create([
      'name' => 'Facets Summary',
      'id' => 'facets_summary_test',
      'facet_source_id' => 'search_api:views_page__test__page_1',
      'facets' => [
        'authored_on' => [
          'checked' => TRUE,
          'show_count' => FALSE,
        ],
      ],
      'processor_configs' => [
        'show_count' => [
          'processor_id' => 'show_count',
        ],
      ],
    ])->save();
    $this->drupalPlaceBlock('facets_summary_block:facets_summary_test');
    $this->drupalPlaceBlock('facets_form:search_api:views_page__test__page_1');
    $this->drupalGet('test');
    $assert->elementsCount('css', '.views-row', 2);
    $assert->pageTextContains('Llama');
    $assert->pageTextContains('Emu');
    $this->assertFacetsSummary(2, NULL);
    $form = $assert->elementExists('css', 'form#facets-form');
    $assert->elementExists('css', 'input#edit-authored-on-from-date', $form);
    $assert->elementExists('css', 'input#edit-authored-on-to-date', $form);
    // Test greater or equals operator.
    $page->fillField('edit-authored-on-from-date', '2021-08-16');
    $form->pressButton('Search');
    $this->assertCurrentUrl('test', ['f' => ['authored_on:2021-08-16~']]);
    $assert->elementsCount('css', '.views-row', 1);
    $assert->pageTextNotContains('Llama');
    $assert->pageTextContains('Emu');
    $this->assertFacetsSummary(1, '(-) After Mon, 08/16/2021 - 00:00');
    // Test smaller or equals operator.
    $page->fillField('edit-authored-on-from-date', '');
    $page->fillField('edit-authored-on-to-date', '2021-08-16');
    $form->pressButton('Search');
    $this->assertCurrentUrl('test', ['f' => ['authored_on:~2021-08-16']]);
    $assert->elementsCount('css', '.views-row', 1);
    $assert->pageTextContains('Llama');
    $assert->pageTextNotContains('Emu');
    $this->assertFacetsSummary(1, '(-) Before Mon, 08/16/2021 - 00:00');
    // Test between operator.
    $page->fillField('edit-authored-on-from-date', '2021-08-16');
    $page->fillField('edit-authored-on-to-date', '2021-08-17');
    $form->pressButton('Search');
    $this->assertCurrentUrl('test', ['f' => ['authored_on:2021-08-16~2021-08-17']]);
    $assert->elementsCount('css', '.views-row', 1);
    $assert->pageTextNotContains('Llama');
    $assert->pageTextContains('Emu');
    $this->assertFacetsSummary(1, '(-) Between Mon, 08/16/2021 - 00:00 and Tue, 08/17/2021 - 00:00');
  }

  /**
   * Asserts the facets summary result.
   *
   * @param int $expected_count
   *   The number of expected results.
   * @param string|null $expected_facet
   *   The expected.
   */
  protected function assertFacetsSummary(int $expected_count, ?string $expected_facet): void {
    $assert = $this->assertSession();

    $summary_count = \Drupal::translation()->formatPlural($expected_count, '1 result found', '@count results found');
    $assert->elementTextContains('css', '.source-summary-count', $summary_count);

    if (is_null($expected_facet)) {
      $assert->elementNotExists('css', '.facet-summary-item--facet');
      return;
    }

    $assert->elementsCount('css', '.facet-summary-item--facet', 1);
    $assert->elementTextContains('css', '.facet-summary-item--facet', $expected_facet);
  }

}
