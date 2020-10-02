<?php

namespace Drupal\facets_select2_multi_checkboxes_widget\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The Select2 Multi Checkboxes widget.
 *
 * @FacetsWidget(
 *   id = "select2_multi_checkboxes",
 *   label = @Translation("Select2 Multi Checkboxes"),
 *   description = @Translation("A configurable widget that shows a Select2 Multi Checkboxes."),
 * )
 */
class Select2MultiCheckboxesWidget extends WidgetPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The key-value store for entity_autocomplete.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, KeyValueStoreInterface $key_value_store) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
    $this->keyValueStore = $key_value_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'default_option_label' => 'Choose',
        'width' => '100%',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $config = $this->getConfiguration();

    $items = [];
    $active_items = [];
    foreach ($facet->getResults() as $result) {
      if (empty($result->getUrl())) {
        continue;
      }

      $count = $result->getCount();
      $this->showNumbers = $this->getConfiguration()['show_numbers'] && ($count !== NULL);
      $items[$result->getUrl()->toString()] = ($this->showNumbers ? sprintf('%s (%d)', $result->getDisplayValue(), $result->getCount()) : $result->getDisplayValue());
      if ($result->isActive()) {
        $active_items[] = $result->getUrl()->toString();
      }
    }

    $element = [
      '#type' => 'container',
      '#facet' => $facet,
      '#attributes' => [
        'class' => ['facets-widget-select2_multi_checkboxes'],
      ],
    ];

    if (empty($items)) {
      $element['#attributes']['class'][] = 'hidden';
    }

    $element['content'] = [
      '#type' => 'select2',
      '#options' => $items,
      '#required' => FALSE,
      '#value' => $active_items,
      '#multiple' => !$facet->getShowOnlyOneResult(),
      '#name' => $facet->getName(),
      '#title' => $facet->get('show_title') ? $facet->getName() : '',
      '#attributes' => [
        'data-drupal-facet-id' => $facet->id(),
        'data-drupal-selector' => 'facet-' . $facet->id(),
        'class' => ['js-facets-select2-multi-checkboxes', 'js-facets-widget'],
      ],
      '#attached' => [
        'library' => ['facets_select2_multi_checkboxes_widget/select2-multi-checkboxes-widget'],
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
      '#select2' => [
        'placeholder' => $config['default_option_label'],
        'placeholderForSearch' => t("Search"),
        'search' => $config['search'],
        'dropdownAutoWidth' => false,
        'width' => $config['width']
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();

    $form = parent::buildConfigurationForm($form, $form_state, $facet);

    $form['default_option_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default option label'),
      '#default_value' => $config['default_option_label'],
    ];

    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field width'),
      '#default_value' => $config['width'],
      '#description' => $this->t("Define a width for the select2 field. It can be either 'element', 'computedstyle', 'style', 'resolve' or any possible CSS unit. E.g. 500px, 50%, 200em. See the <a href='https://select2.org/appearance#container-width'>select2 documentation</a> for further explanations."),
      '#required' => TRUE,
      '#size' => '12',
      '#pattern' => "([0-9]*\.[0-9]+|[0-9]+)(cm|mm|in|px|pt|pc|em|ex|ch|rem|vm|vh|vmin|vmax|%)|element|computedstyle|style|resolve|auto|initial|inherit",
    ];

    $form['search'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show search field'),
      '#default_value' => $config['search'],
    );

    return $form;
  }
}
