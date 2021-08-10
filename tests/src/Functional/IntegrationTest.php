<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form\Functional;

use Drupal\facets\Entity\Facet;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets\Functional\ExampleContentTrait;

/**
 * Test the facets form.
 *
 * @group facets_form
 */
class IntegrationTest extends BrowserTestBase {

  use ExampleContentTrait;
  use BlockTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'facets_form',
    'facets_search_api_dependency',
    'node',
    'search_api',
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

    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertSame(5, $this->indexItems('database_search_index'));
  }

  /**
   * Test the facets form.
   */
  public function testFacetsForm(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalLogin($this->drupalCreateUser(['administer blocks']));
    $this->createFacet('Emu', 'emu');
    $this->createFacet('Llama', 'llama');
    $facet = Facet::load('llama');
    $facet->setWidget('facets_form_dropdown');
    $facet->save();
    // Place the Facets Form block for a view page source.
    $block = $this->drupalPlaceBlock('facets_form:search_api:views_page__search_api_test_view__page_1');
    $this->drupalGet('admin/structure/block/manage/' . $block->id());
    $assert->fieldValueEquals('Submit button', 'Search');
    $assert->fieldValueEquals('Reset button', 'Clear filters');
    $page->fillField('Submit button', 'Apply');
    $page->fillField('Reset button', 'Reset');
    $assert->fieldExists('Llama');
    $page->checkField('Llama');
    $page->pressButton('Save block');
    $this->drupalGet('search-api-test-fulltext');
    $assert->elementsCount('css', '.views-row', 5);
    $form = $assert->elementExists('css', 'form#facets-form');

    // The form contains only the widget from the module.
    $assert->elementExists('css', 'select#edit-llama--2', $form);
    $assert->elementNotExists('css', 'select#edit-emu--2', $form);
    $assert->elementNotExists('css', 'select#edit-alpaca--2', $form);

    // The form submits and filters the results.
    $page->selectFieldOption('llama[]', 'article');
    $assert->buttonExists('Apply', $form)->press();
    $assert->addressEquals('search-api-test-fulltext?f[0]=llama:article');
    $assert->elementsCount('css', '.views-row', 2);
    $page->selectFieldOption('llama[]', 'item');
    $assert->buttonExists('Apply', $form)->press();
    $assert->addressEquals('search-api-test-fulltext?f[0]=llama:item');
    $assert->elementsCount('css', '.views-row', 3);
    $page->clickLink('Reset');
    $assert->addressNotEquals('f[0]=llama');
    $assert->elementsCount('css', '.views-row', 5);

    // Test the CheckboxWidget widget.
    $facet->setWidget('facets_form_checkbox', ['indent_class' => 'super-indented']);
    $facet->save();
    $this->drupalGet('search-api-test-fulltext');
    $form->checkField('item');
    $form->pressButton('Apply');
    $assert->addressEquals('search-api-test-fulltext?f[0]=llama:item');
    $assert->checkboxChecked('item', $form);
    $assert->elementsCount('css', '.views-row', 3);
    $form->checkField('article');
    $form->pressButton('Apply');
    $assert->addressEquals('search-api-test-fulltext?f[0]=llama:article&f[1]=llama:item');
    $assert->checkboxChecked('item', $form);
    $assert->checkboxChecked('article', $form);
    $assert->elementsCount('css', '.views-row', 5);

    // Change configured facets.
    $this->createFacet('Alpaca', 'alpaca');
    $facet = Facet::load('alpaca');
    $facet->setWidget('facets_form_dropdown');
    $facet->save();
    $this->drupalGet('admin/structure/block/manage/' . $block->id());
    $assert->fieldNotExists('Emu');
    $assert->fieldExists('Llama');
    $assert->fieldExists('Alpaca');
    $page->checkField('Alpaca');
    $page->uncheckField('Llama');
    $page->pressButton('Save block');
    $this->drupalGet('search-api-test-fulltext');
    $form = $assert->elementExists('css', 'form#facets-form');
    $assert->elementExists('css', 'select#edit-alpaca--2', $form);
    $assert->elementNotExists('css', 'select#edit-llama--2', $form);
    $assert->elementNotExists('css', 'select#edit-emu--2', $form);

  }

}
