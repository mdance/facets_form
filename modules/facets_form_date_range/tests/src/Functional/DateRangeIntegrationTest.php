<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form_date_range\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\facets\Functional\BlockTestTrait;
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
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
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
    $this->drupalPlaceBlock('facets_form:search_api:views_page__test__page_1');
    $this->drupalGet('test');
    $assert->elementsCount('css', '.views-row', 2);
    $assert->pageTextContains('Llama');
    $assert->pageTextContains('Emu');
    $form = $assert->elementExists('css', 'form#facets-form');
    $assert->elementExists('css', 'input#edit-authored-on-from-date', $form);
    $assert->elementExists('css', 'input#edit-authored-on-to-date', $form);
    // Test greater or equals operator.
    $page->fillField('edit-authored-on-from-date', '2021-08-16');
    $form->pressButton('Search');
    $assert->addressEquals('test?f[0]=authored_on%3A2021-08-16~');
    $assert->elementsCount('css', '.views-row', 1);
    $assert->pageTextNotContains('Llama');
    $assert->pageTextContains('Emu');
    // Test smaller or equals operator.
    $page->fillField('edit-authored-on-from-date', '');
    $page->fillField('edit-authored-on-to-date', '2021-08-16');
    $form->pressButton('Search');
    $assert->addressEquals('test?f[0]=authored_on%3A~2021-08-16');
    $assert->elementsCount('css', '.views-row', 1);
    $assert->pageTextContains('Llama');
    $assert->pageTextNotContains('Emu');
    // Test between operator.
    $page->fillField('edit-authored-on-from-date', '2021-08-16');
    $page->fillField('edit-authored-on-to-date', '2021-08-17');
    $form->pressButton('Search');
    $assert->addressEquals('test?f[0]=authored_on%3A2021-08-16~2021-08-17');
    $assert->elementsCount('css', '.views-row', 1);
    $assert->pageTextNotContains('Llama');
    $assert->pageTextContains('Emu');
  }

}
