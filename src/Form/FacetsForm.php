<?php

declare(strict_types = 1);

namespace Drupal\facets_form\Form;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\facets\Utility\FacetsUrlGenerator;
use Drupal\facets_form\FacetsFormWidgetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes the facets as a form.
 */
class FacetsForm extends FormBase {

  /**
   * The facets manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetsManager;

  /**
   * The facets url generator.
   *
   * @var \Drupal\facets\Utility\FacetsUrlGenerator
   */
  protected $facetsUrlGenerator;

  /**
   * Constructs an instance of ListFacetsForm.
   *
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facets_manager
   *   The facets manager.
   * @param \Drupal\facets\Utility\FacetsUrlGenerator $facets_url_generator
   *   The facets url generator.
   */
  public function __construct(DefaultFacetManager $facets_manager, FacetsUrlGenerator $facets_url_generator) {
    $this->facetsManager = $facets_manager;
    $this->facetsUrlGenerator = $facets_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('facets.manager'),
      $container->get('facets.utility.url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $source_id = NULL) {
    if (!$source_id) {
      return [];
    }

    // @todo Set proper cache metadata.
    $cache = new CacheableMetadata();

    $facets = $this->facetsManager->getFacetsByFacetSourceId($source_id);
    if (!$facets) {
      $cache->applyTo($form);
      return $form;
    }

    // Sort facets by weight.
    uasort($facets, function (FacetInterface $a, FacetInterface $b) {
      return $a->getWeight() <=> $b->getWeight();
    });

    foreach ($facets as $facet) {
      $widget = $facet->getWidgetInstance();
      if ($widget instanceof FacetsFormWidgetInterface) {
        $form['facets'][$facet->id()] = $this->facetsManager->build($facet);
      }
    }

    if (!isset($form['facets'])) {
      $cache->applyTo($form);
      return $form;
    }

    $form_state->set('source_id', $source_id);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#op' => 'submit',
    ];

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear filters'),
      '#op' => 'reset',
    ];

    $cache->applyTo($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_url = Url::fromRoute('<current>');
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#op'] === 'reset') {
      $form_state->setRedirectUrl($current_url);
      return;
    }

    $active_filters = [];
    foreach ($this->facetsManager->getFacetsByFacetSourceId($form_state->get('source_id')) as $facet) {
      $widget = $facet->getWidgetInstance();
      if ($widget instanceof FacetsFormWidgetInterface) {
        $active_filters[$facet->id()] = $widget->prepareValueForUrl($facet, $form, $form_state);
      }
    }

    $active_filters = array_filter($active_filters);
    if ($active_filters) {
      $url = $this->facetsUrlGenerator->getUrl($active_filters, FALSE);
      $form_state->setRedirectUrl($url);
      return;
    }

    // If there are no active filters, we redirect to the current URL without
    // any filters in the URL.
    $form_state->setRedirectUrl($current_url);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'facets_form';
  }

}
