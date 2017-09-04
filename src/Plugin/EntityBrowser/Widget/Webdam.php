<?php

namespace Drupal\media_webdam\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media_webdam\WebdamInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\media_entity\Entity\Media;

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
   * The webdam interface.
   *
   * @var \Drupal\media_webdam\WebdamInterface
   */
  protected $webdam;

  /**
   * Webdam constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   * @param \Drupal\media_webdam\WebdamInterface $webdam_interface
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, WebdamInterface $webdam){
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->webdam = $webdam;
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
      $container->get('media_webdam.webdam')
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
   *
   * TODO: This is a mega-function which needs to be refactored.  Therefore it has been thouroughly documented
   *
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    //Start by inheriting parent form
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    //How many values are allowed for this media field
    $field_cardinality = $form_state->get(['entity_browser', 'validators', 'cardinality', 'cardinality']);
    //This form is submitted and rebuilt when a folder is clicked.  The triggering element identifies which folder button was clicked
    $trigger_elem = $form_state->getTriggeringElement();
    //The webdam folder ID of the current folder being rendered - Start with zero which is the root folder
    $current_folder_id = 0;
    //The webdam folder object - Start with NULL to represent the root folder
    $current_folder = NULL;

    //If a button has been clicked that represents a webdam folder
    if (isset($trigger_elem['#name']) && $trigger_elem['#name'] == 'webdam_folder') {
      //Set the current folder id to the id of the folder that was clicked
      $current_folder_id = intval($form_state->getTriggeringElement()['#webdam_folder_id']);
    }
    //If the current folder is not zero then fetch information about the sub folder being rendered
    if($current_folder_id !== 0){
      //Fetch the folder object from webdam
      $current_folder = $this->webdam->getFolder($current_folder_id);
      //Fetch a list of assets for the folder from webdam
      $folder_assets = $this->webdam->getFolderAssets($current_folder_id);
      //Store the list of folders for rendering later
      $folders = $folder_assets->folders;
      //Store the list of items/assets for rendering later
      $folder_items = $folder_assets->items;
    }else{
      //The webdam root folder is fetched differently because it can only contain subfolders (not assets)
      $folders = $this->webdam->getTopLevelFolders();
    }

    //Initial breadcrumb array representing the root folder only
    $breadcrumbs = [
      '0' => 'Home'
    ];
    //If the form has been rebuilt due to navigating between folders, look for the breadcrumb container
    if(isset($form_state->getCompleteForm()['widget'])){
      if(!empty($form_state->getCompleteForm()['widget']['breadcrumb-container']['#breadcrumbs'])){
        //If breadcrumbs already exist, use them instead of the initial default value
        $breadcrumbs = $form_state->getCompleteForm()['widget']['breadcrumb-container']['#breadcrumbs'];
      }
    }
    //If the folder being rendered is already in the breadcrumb trail and the breadcrumb trail is longer than 1 (i.e. root folder only)
    if(array_key_exists($current_folder_id,$breadcrumbs) && count($breadcrumbs) > 1){
      //This indicates that the user has navigated "Up" the folder structure 1 or more levels
      do{
        //Go to the end of the breadcrumb array
        end($breadcrumbs);
        //Fetch the folder id of the last breadcrumb
        $id = key($breadcrumbs);
        //If the current folder id does not match the folder id of the last breadcrumb
        if($id != $current_folder_id && count($breadcrumbs) > 1) {
          //Remove the last breadcrumb since the user has navigated "Up" at least 1 folder
          array_pop($breadcrumbs);
        }
        //If the folder id of the last breadcrumb does not equal the current folder id then keep removing breadcrumbs from the end
      }while($id != $current_folder_id && count($breadcrumbs) > 1);
    }
    //If the parent folder id of the current folder is in the breadcrumb trail then the user MIGHT have navigated down into a subfolder
    if(array_key_exists($current_folder->parent,$breadcrumbs)){
      //Go to the end of the breadcrumb array
      end($breadcrumbs);
      //If the last folder id in the breadcrumb equals the parent folder id of the current folder the the user HAS navigated down into a subfolder
      if(key($breadcrumbs) == $current_folder->parent){
        //Add the current folder to the breadcrumb
        $breadcrumbs[$current_folder_id] = $current_folder->name;
      }
    }
    //Reset the breadcrumb array so that it can be rendered in order
    reset($breadcrumbs);
    //Create a container for the breadcrumb
    $form['breadcrumb-container'] = [
      '#type' => 'container',
      //custom element property to store breadcrumbs array.  This is fetched from the form state every time the form is rebuilt due to navigating between folders
      '#breadcrumbs' => $breadcrumbs,
    ];
    //Add the breadcrumb buttons to the form
    foreach ($breadcrumbs as $folder_id => $folder_name){
      $form['breadcrumb-container'][$folder_id] = [
        '#type' => 'button',
        '#value' => $folder_name,
        '#name' => 'webdam_folder',
        '#webdam_folder_id' => $folder_id,
        '#webdam_parent_folder_id' => $folder_name,
      ];
    }

    //Add container for assets (and folder buttons)
    $form['asset-container'] = [
      '#type' => 'container',
    ];
    //Add folder buttons to form
    foreach ($folders as $folder){
      $form['asset-container'][$folder->id] = [
        '#type' => 'button',
        '#value' => $folder->name,
        '#name' => 'webdam_folder',
        '#webdam_folder_id' => $folder->id,
        '#webdam_parent_folder_id' => $current_folder->parent,
      ];
    }
    //Assets are rendered as #options for a checkboxes element.  Start with an empty array.
    $assets = [];
    //Add to the assets array
    foreach ($folder_items as $folder_item) {
      if($folder_item->type == 'asset'){
        //wrap asset name in a paragraph tag
        $assets[$folder_item->id] = '<p class="webdam-asset-fielname">'.$folder_item->name.'</p>';
        //If the asset has a thumbnail it should be rendered
        if(!empty($folder_item->thumbnailurls)){
          //Multiple thumbnail sizes are available.  Using the 220 width version for now
          $assets[$folder_item->id] .= '<img class="webdam-asset-thumbnail" src="'.$folder_item->thumbnailurls[2]->url.'" alt="'.$folder_item->name.'" />';
        }
      }
    }
    //If the assets array is not empty then add the assets to the form as checkboxes
    if(!empty($assets)){
      $form['asset-container']['assets'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Choose one or more assets'),
        '#title_display' => 'invisible',
        '#options' => $assets,
        // Multiple assets will only be accepted if the source field allows more than one value.
        '#multiple' => $field_cardinality != 1 && $this->configuration['multiple'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    foreach ($form_state->getValue(['upload'], []) as $aid) {
      if ($aid !== 0) {
        //TODO: validate form selection
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $assets = [];
    if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      foreach ($form_state->getValue(['assets'], []) as $aid) {
        if ($aid !== 0) {
          $webdam_asset = $this->webdam->getAsset($aid);
          ksm($webdam_asset);
          $media_asset = Media::create([
            'bundle' => 'webdam',
            'uid' => '1',
            'langcode' => 'en',
            'status' => Media::PUBLISHED,
            'name' => $webdam_asset->name,
            'field_asset_id' => $aid,
          ]);
          $media_asset->save();
          $assets[] = $media_asset;
        }
      }
    }
    // $this->clearFormValues($element, $form_state);
    $this->selectEntities($assets, $form_state);
  }

  /**
   * Clear values from upload form element.
   *
   * @param array $element
   *   Upload form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * TODO: Determine if this function is still needed.  It was initially copied from Upload widget class in the entity browser module
   *
   */
  protected function clearFormValues(array &$element, FormStateInterface $form_state) {
    // We propagated entities to the other parts of the system. We can now remove
    // them from our values.
    // $form_state->setValueForElement($element['upload']['fids'], '');
    // NestedArray::setValue($form_state->getUserInput(), $element['upload']['fids']['#parents'], '');
  }

  /**
   * {@inheritdoc}
   *
   * TODO: Add more settings for configuring this widget
   *
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Accept multiple files'),
      '#default_value' => $this->configuration['multiple'],
      '#description' => $this->t('Multiple assets will only be accepted if the source field allows more than one value.'),
    ];
    return $form;
  }
}
