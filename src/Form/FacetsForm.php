<?php

declare(strict_types = 1);

namespace Drupal\facets_form\Form;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The facets form config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs an instance of ListFacetsForm.
   *
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facets_manager
   *   The facets manager.
   * @param \Drupal\facets\Utility\FacetsUrlGenerator $facets_url_generator
   *   The facets url generator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(DefaultFacetManager $facets_manager, FacetsUrlGenerator $facets_url_generator, ConfigFactoryInterface $config_factory) {
    $this->facetsManager = $facets_manager;
    $this->facetsUrlGenerator = $facets_url_generator;
    $this->config = $config_factory->get('facets_form.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('facets.manager'),
      $container->get('facets.utility.url_generator'),
      $container->get('config.factory'),
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
      '#value' => $this->config->get('submit_text'),
      '#op' => 'submit',
    ];

    $form['actions']['reset'] = [
      '#type' => 'link',
      '#title' => $this->config->get('reset_text'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#url' =>  Url::fromRoute('<current>'),
    ];

    $cache->applyTo($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
    $form_state->setRedirectUrl(Url::fromRoute('<current>'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'facets_form';
  }

}
