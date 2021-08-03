<?php

namespace Drupal\facets_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets_form\Form\FacetsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

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
   * The facets form config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder,  ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->config = $config_factory->get('facets_form.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['submit_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit button'),
      '#description' => $this->t('Add the text which overrides the facet submit label button.'),
      '#default_value' => $config['submit_text'] ?? $this->config->get('submit_text'),
      '#required' => TRUE,
    ];
    $form['reset_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reset button'),
      '#description' => $this->t('Add the text which overrides the facet reset label button.'),
      '#default_value' => $config['reset_text'] ?? $this->config->get('reset_text'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    $form = $this->formBuilder->getForm(FacetsForm::class, $this->getDerivativeId());

    $form['actions']['submit']['#value'] = $config['submit_text'];
    $form['actions']['reset']['#title'] = $config['reset_text'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Setting values for button labels.
    $this->setConfigurationValue('submit_text', $form_state->getValue('submit_text'));
    $this->setConfigurationValue('reset_text', $form_state->getValue('reset_text'));
  }

}
