<?php

namespace Drupal\facets_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets_form\Form\FacetsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes the facets form as block.
 *
 * @Block(
 *  id = "facets_form",
 *  admin_label = @Translation("Facets form"),
 *  category = @Translation("Facets"),
 *  deriver = "Drupal\facets_form\Plugin\Block\Deriver\FacetsFormBlockDeriver",
 * )
 */
class FacetsFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new form instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return $this->formBuilder->getForm(FacetsForm::class, $this->getDerivativeId());
  }

}
