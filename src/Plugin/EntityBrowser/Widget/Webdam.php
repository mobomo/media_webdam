<?php

namespace Drupal\media_webdam\Plugin\EntityBrowser\Widget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Uses a view to provide entity listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "webdam",
 *   label = @Translation("Webdam"),
 *   description = @Translation("Webdam asset browser"),
 *   auto_select = FALSE
 * )
 */
class Webdam extends WidgetBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Upload constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, ModuleHandlerInterface $module_handler, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->moduleHandler = $module_handler;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'submit_text' => $this->t('Select assets'),
      'multiple' => TRUE,
    ] + 
    parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $field_cardinality = $form_state->get(['entity_browser', 'validators', 'cardinality', 'cardinality']);
    $form['assets'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Choose one or more assets'),
      '#title_display' => 'invisible',
      '#options' => array( 'asset_id_1' => 'Asset 1', 'asset_id_2' => 'Asset 2', 'asset_id_3' => 'Asset 3' ),
      // Multiple assets will only be accepted if the source field allows
      // more than one value.
      '#multiple' => $field_cardinality != 1 && $this->configuration['multiple'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    // $files = [];
    // foreach ($form_state->getValue(['upload'], []) as $fid) {
    //   $files[] = $this->entityTypeManager->getStorage('file')->load($fid);
    // }
    //return $files;
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    // if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
    //   $files = $this->prepareEntities($form, $form_state);
    //   array_walk(
    //     $files,
    //     function (FileInterface $file) {
    //       $file->setPermanent();
    //       $file->save();
    //     }
    //   );
    //   $this->selectEntities($files, $form_state);
    //   $this->clearFormValues($element, $form_state);
    // }
  }

  /**
   * Clear values from upload form element.
   *
   * @param array $element
   *   Upload form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function clearFormValues(array &$element, FormStateInterface $form_state) {
    // We propagated entities to the other parts of the system. We can now remove
    // them from our values.
    // $form_state->setValueForElement($element['upload']['fids'], '');
    // NestedArray::setValue($form_state->getUserInput(), $element['upload']['fids']['#parents'], '');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // $form['upload_location'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Upload location'),
    //   '#default_value' => $this->configuration['upload_location'],
    // ];
    $form['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Accept multiple files'),
      '#default_value' => $this->configuration['multiple'],
      '#description' => $this->t('Multiple assets will only be accepted if the source field allows more than one value.'),
    ];
    // $form['extensions'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Allowed file extensions'),
    //   '#description' => $this->t('Separate extensions with a space or comma and do not include the leading dot.'),
    //   '#default_value' => $this->configuration['extensions'],
    //   '#element_validate' => [[static::class, 'validateExtensions']],
    //   '#required' => TRUE,
    // ];

    // if ($this->moduleHandler->moduleExists('token')) {
    //   $form['token_help'] = [
    //     '#theme' => 'token_tree_link',
    //     '#token_types' => ['file'],
    //   ];
    //   $form['upload_location']['#description'] = $this->t('You can use tokens in the upload location.');
    // }

    return $form;
  }

}
