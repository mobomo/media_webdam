<?php

namespace Drupal\media_webdam\Plugin\EntityBrowser\Widget;

use cweagans\webdam\Entity\Folder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media_webdam\WebdamInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\media_entity\Entity\Media;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Language\LanguageManagerInterface;

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
   * The webdam interface.
   *
   * @var \Drupal\media_webdam\WebdamInterface
   */
  protected $webdam;

  /**
   * The current user account.
   *
   * @var AccountInterface
   */
  protected $user;

  /**
   * The current user account.
   *
   * @var LanguageManagerInterface
   */
  protected $language_manager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entity_type_bundle_info;

  /**
   * Webdam constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   * @param \Drupal\media_webdam\WebdamInterface $webdam
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, WebdamInterface $webdam, EntityTypeBundleInfoInterface $entity_type_bundle_info, AccountInterface $account, LanguageManagerInterface $language_manager){
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->webdam = $webdam;
    $this->entity_type_bundle_info = $entity_type_bundle_info;
    $this->user = $account;
    $this->language_manager = $language_manager;
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
      $container->get('media_webdam.webdam'),
      $container->get('entity_type.bundle.info'),
      $container->get('current_user'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBreadcrumb(Folder $current_folder, array $breadcrumbs = []) {
    //If the folder being rendered is already in the breadcrumb trail and the breadcrumb trail is longer than 1 (i.e. root folder only)
    if(array_key_exists($current_folder->id,$breadcrumbs) && count($breadcrumbs) > 1){
      //This indicates that the user has navigated "Up" the folder structure 1 or more levels
      do{
        //Go to the end of the breadcrumb array
        end($breadcrumbs);
        //Fetch the folder id of the last breadcrumb
        $id = key($breadcrumbs);
        //If the current folder id does not match the folder id of the last breadcrumb
        if($id != $current_folder->id && count($breadcrumbs) > 1) {
          //Remove the last breadcrumb since the user has navigated "Up" at least 1 folder
          array_pop($breadcrumbs);
        }
        //If the folder id of the last breadcrumb does not equal the current folder id then keep removing breadcrumbs from the end
      }while($id != $current_folder->id && count($breadcrumbs) > 1);
    }
    //If the parent folder id of the current folder is in the breadcrumb trail then the user MIGHT have navigated down into a subfolder
    if(is_object($current_folder) && property_exists($current_folder, 'parent') && array_key_exists($current_folder->parent, $breadcrumbs)){
      //Go to the end of the breadcrumb array
      end($breadcrumbs);
      //If the last folder id in the breadcrumb equals the parent folder id of the current folder the the user HAS navigated down into a subfolder
      if(key($breadcrumbs) == $current_folder->parent){
        //Add the current folder to the breadcrumb
        $breadcrumbs[$current_folder->id] = $current_folder->name;
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
        '#prefix' => '<span class="webdam-breadcrumb-trail">',
        '#suffix' => '</span>',
        '#attributes' => [
          'class' => ['webdam-browser-breadcrumb'],
        ]
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Create a custom pager
   *
   */
  public function getPager(Folder $current_folder, int $page, int $num_per_page) {
    // Add container for pager
    $form['pager-container'] = [
      '#type' => 'container',
      //Store page number in container so it can be retrieved from the form state
      '#page' => $page,
      '#attributes' => [
        'class' => ['webdam-asset-browser-pager']
      ]
    ];
    //If not on the first page
    if($page > 0){
      //Add a button to go to the first page
      $form['pager-container']['first'] = [
        '#type' => 'button',
        '#value' => 'First',
        '#name' => 'webdam_pager',
        '#webdam_page' => 0,
        '#attributes' => [
          'class' => ['page-button','page-first'],
        ]
      ];
      //Add a button to go to the previous page
      $form['pager-container']['previous'] = [
        '#type' => 'button',
        '#value' => 'Previous',
        '#name' => 'webdam_pager',
        '#webdam_page' => $page - 1,
        '#attributes' => [
          'class' => ['page-button','page-previous'],
        ]
      ];
    }
    //Last available page based on number of assets in folder divided by number of assets to show per page
    $last_page = floor($current_folder->numassets / $num_per_page);
    //First page to show in the pager.  Try to put the button for the current page in the middle by starting at the current page number minus 4
    $start_page = max(0, $page - 4);
    //Last page to show in the pager.  Don't go beyond the last available page
    $end_page = min($start_page + 9, $last_page);
    //Create buttons for pages from start to end
    for($i = $start_page; $i <= $end_page; $i++){
      $form['pager-container']['page_'.$i] = [
        '#type' => 'button',
        '#value' => $i + 1,
        '#name' => 'webdam_pager',
        '#webdam_page' => $i,
        '#attributes' => [
          'class' => [($i == $page ? 'page-current' : ''), 'page-button'],
        ]
      ];
    }
    //If not on the last page
    if($end_page > $page){
      //Add a button to go to the next page
      $form['pager-container']['next'] = [
        '#type' => 'button',
        '#value' => 'Next',
        '#name' => 'webdam_pager',
        '#webdam_page' => $page + 1,
        '#attributes' => [
          'class' => ['page-button', 'page-next'],
        ]
      ];
      //Add a button to go to the last page
      $form['pager-container']['last'] = [
        '#type' => 'button',
        '#value' => 'Last',
        '#name' => 'webdam_pager',
        '#webdam_page' => $last_page,
        '#attributes' => [
          'class' => ['page-button', 'page-last'],
        ]
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Create form elements for sorting and filtering/searching
   *
   */
  public function getFilterSort() {
    // Add container for pager
    $form['filter-sort-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['filter-sort-container']
      ]
    ];
    // Add dropdown for sort by
    $form['filter-sort-container']['sortby'] = [
      '#type' => 'select',
      '#title' => 'Sort by',
      '#options' => ['filename' => 'File name', 'filesize' => 'File size', 'datecreated' => 'Date created', 'datemodified' => 'Date modified'],
      '#default_value' => 'datecreated',
    ];
    // Add dropdown for sort direction
    $form['filter-sort-container']['sortdir'] = [
      '#type' => 'select',
      '#title' => 'Sort direction',
      '#options' => ['asc' => 'Ascending', 'desc' => 'Descending'],
      '#default_value' => 'asc',
    ];
    // Add dropdown for filtering on asset type
    $form['filter-sort-container']['types'] = [
      '#type' => 'select',
      '#title' => 'File type',
      '#options' => ['' => 'All', 'image' => 'Image', 'audiovideo' => 'Audio/Video', 'document' => 'Document', 'presentation' => 'Presentation', 'other' => 'Other'],
      '#default_value' => '',
    ];
    // Add textfield for keyword search
    $form['filter-sort-container']['query'] = [
      '#type' => 'textfield',
      '#title' => 'Search',
      '#size' => 24,
    ];
    // Add submit button to apply sort/filter criteria
    $form['filter-sort-container']['filter-sort-submit'] = [
      '#type' => 'button',
      '#value' => 'Apply',
      '#name' => 'filter_sort_submit',
    ];
    // Add form reset button.
    $form['filter-sort-container']['filter-sort-reset'] = [
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => [
        'class' => 'button',
        'type' => 'reset',
        'value' => 'Reset',
      ],
    ];
    return $form;
  }

    /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    //If this is not the current entity browser widget being rendered
    if($this->uuid() != $form_state->getStorage()['entity_browser_current_widget']){
      //return an empty array
      return [];
    }
    //Start by inheriting parent form
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    // Attach the modal library.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    //This form is submitted and rebuilt when a folder is clicked.  The triggering element identifies which folder button was clicked
    $trigger_elem = $form_state->getTriggeringElement();
    //Initialize current_folder
    $current_folder = new Folder();
    //Default current folder id to zero which represents the root folder.
    $current_folder->id = 0;
    //Default current folder parent id to zero which represents the root folder.
    $current_folder->parent = 0;
    //Default current folder name to 'Home' which represents the root folder
    $current_folder->name = 'Home';
    //Default current page to first page
    $page = 0;
    //Number of assets to show per page
    $num_per_page = 10;
    //Initial breadcrumb array representing the root folder only
    $breadcrumbs = [
      '0' => 'Home'
    ];
    //If the form state contains the widget then pull values for the current state
    if(isset($form_state->getCompleteForm()['widget'])){
      //assign $widget for convenience
      $widget = $form_state->getCompleteForm()['widget'];
      if(isset($widget['pager-container']) && is_numeric($widget['pager-container']['#page'])){
        //Set the page number to the value stored in the form state
        $page = intval($widget['pager-container']['#page']);
      }
      if(isset($widget['asset-container']) && is_numeric($widget['asset-container']['#webdam_folder_id'])) {
        //Set current folder id to the value stored in the form state
        $current_folder->id = $widget['asset-container']['#webdam_folder_id'];
      }
      if(isset($widget['breadcrumb-container']) && is_array($widget['breadcrumb-container']['#breadcrumbs'])) {
        //Set the breadcrumbs to the value stored in the form state
        $breadcrumbs = $widget['breadcrumb-container']['#breadcrumbs'];
      }
    }
    //If the form has been submitted
    if (isset($trigger_elem)){
      //If a folder button has been clicked
      if ($trigger_elem['#name'] == 'webdam_folder') {
        //Set the current folder id to the id of the folder that was clicked
        $current_folder->id = intval($trigger_elem['#webdam_folder_id']);
        //Reset page to zero if we have navigated to a new folder
        $page = 0;
      }
      //If a pager button has been clicked
      if ($trigger_elem['#name'] == 'webdam_pager') {
        //Set the current folder id to the id of the folder that was clicked
        $page = intval($trigger_elem['#webdam_page']);
      }
      //If the filter/sort submit button has been clicked
      if ($trigger_elem['#name'] == 'filter_sort_submit') {
        //Reset page to zero
        $page = 0;
      }
    }
    //Offset used for pager
    $offset = $num_per_page * $page;
    //Parameters for searching, sorting, and filtering
    $params = [
      'limit' => $num_per_page,
      'offset' => $offset,
      'sortby' => $form_state->getValue('sortby'),
      'sortdir' => $form_state->getValue('sortdir'),
      'types' => $form_state->getValue('types'),
      'query' => $form_state->getValue('query'),
      'folderid' => $current_folder->id,
    ];
    //If the current folder is not zero then fetch information about the sub folder being rendered
    if($current_folder->id){
      //Fetch the folder object from webdam
      $current_folder = $this->webdam->getFolder($current_folder->id);
      //Fetch a list of assets for the folder from webdam
      $folder_assets = $this->webdam->getFolderAssets($current_folder->id, $params);
      //If there is a filter applied for the file type
      if(!empty($params['types'])){
        //Override number of assets on current folder to make number of search results so pager works correctly
        $current_folder->numassets = $folder_assets->facets->types->{$params['types']};
      }
      //Store the list of folders for rendering later
      $folders = $folder_assets->folders;
      //Set items to array of assets in the current folder
      $items = $folder_assets->items;
    }else{
      //The webdam root folder is fetched differently because it can only contain subfolders (not assets)
      $folders = $this->webdam->getTopLevelFolders();
    }
    //If searching by keyword
    if(!empty($params['query'])){
      //Fetch search results from webdam
      $search_results = $this->webdam->searchAssets($params);
      //Override number of assets on current folder to make number of search results so pager works correctly
      $current_folder->numassets = $search_results['total_count'];
      //Set items to array of assets in the search result
      $items = $search_results['assets'];
    }
    //Add the breadcrumb to the form
    $form += $this->getBreadcrumb($current_folder, $breadcrumbs);
    //Add the filter and sort options to the form
    $form += $this->getFilterSort();
    //Add container for assets (and folder buttons)
    $form['asset-container'] = [
      '#type' => 'container',
      //Store the current folder id in the form so it can be retrieved from the form state
      '#webdam_folder_id' => $current_folder->id,
    ];
    // Add folder buttons to form
    foreach ($folders as $folder){
      $form['asset-container'][$folder->id] = [
        '#type' => 'button',
        '#value' => $folder->name,
        '#name' => 'webdam_folder',
        '#webdam_folder_id' => $folder->id,
        '#webdam_parent_folder_id' => $current_folder->parent,
        '#attributes' => [
          'class' => ['webdam-browser-asset'],
        ],
      ];
    }
    //Assets are rendered as #options for a checkboxes element.  Start with an empty array.
    $assets = [];
    //Add to the assets array
    if (isset($items)) {
      foreach ($items as $folder_item) {
        $assets[$folder_item->id] = $this->layoutMediaEntity($folder_item);
      }
    }
    // Add assets to form.
    // IMPORTANT: Do not add #title or #description properties as this will cause this element to get wrapped
    //            in a fieldset which will cause styling problems.
    //            See: \core\lib\Drupal\Core\Render\Element\CompositeFormElementTrait.php
    $form['asset-container']['assets'] = [
      '#type' => 'checkboxes',
      '#theme_wrappers' => ['checkboxes__webdam_assets'],
      '#title_display' => 'invisible',
      '#options' => $assets,
      '#attached' => [
        'library' => [
          'media_webdam/asset_browser',
        ]
      ]
    ];
    //If the number of assets in the current folder is greater than the number of assets to show per page
    if($current_folder->numassets > $num_per_page) {
      //Add the pager to the form
      $form['asset-container'] += $this->getPager($current_folder, $page, $num_per_page);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    //Get webdam asset id's from form state
    $asset_ids = array_filter($form_state->getValue(['assets'], []));
    //Load bundle information
    $bundle = $this->entityTypeManager->getStorage('media_bundle')->load($this->configuration['bundle']);
    //Get the source field for this bundle which stores the webdam asset id
    $source_field = $bundle->type_configuration['source_field'];
    //Query for existing entities
    $existing_ids = $this->entityTypeManager->getStorage('media')->getQuery()
      ->condition('bundle',$bundle->id())
      ->condition($source_field,$asset_ids,'IN')
      ->execute();
    //Load the entities found
    $entities = $this->entityTypeManager->getStorage('media')->loadMultiple($existing_ids);
    //Loop through the existing entities
    foreach ($entities as $entity) {
      //Set the webdam asset id of the current entity
      $asset_id = $entity->get($source_field)->value;
      //If the asset id of the entity is in the list of asset id's selected in the form
      if(in_array($asset_id,$asset_ids)){
        //Remove the asset id from the input so it does not get fetched from webdam and does not get created as a duplicate
        unset($asset_ids[$asset_id]);
      }
    }
    //Fetch the assets from webdam
    $assets = $this->webdam->getAssetMultiple($asset_ids);
    //Loop through the returned webdam assets
    foreach ($assets as $asset) {
      //Initialize entity values
      $entity_values = [
        'bundle' => $bundle->id(),
        //This should be the current user id
        'uid' => $this->user->id(),
        //This should be the current language code
        'langcode' => $this->language_manager->getCurrentLanguage()->getId(),
        //This should map the webdam asset status to the drupal entity status
        'status' => ($asset->status == 'active' ? Media::PUBLISHED : Media::NOT_PUBLISHED),
        //Set the entity name to the webdam asset name
        'name' => $asset->name,
        //Set the chosen source field for this entity to the webdam asset id
        $source_field => $asset->id,
      ];
      //Loop through the mapped fields for this bundle
      foreach ($bundle->field_map as $entity_field => $mapped_field) {
        //Switch for special handling of fields that don't map directly from webdam to the entity
        if(isset($asset->$entity_field)) {
          switch ($entity_field) {
            case 'datecreated':
              $entity_values[$mapped_field] = $asset->date_created_unix;
              break;
            case 'datemodified':
              $entity_values[$mapped_field] = $asset->date_modified_unix;
              break;
            case 'datecaptured':
              $entity_values[$mapped_field] = $asset->datecapturedUnix;
              break;
            //Default handling of fields that should map directly from webdam to the entity
            default:
              $entity_values[$mapped_field] = $asset->$entity_field;
          }
        }
      }
      //Create a new entity to represent the webdam asset
      $entity = $this->entityTypeManager->getStorage('media')->create($entity_values);
      //Save the entity
      $entity->save();
      //Add the new entity to the array of returned entities
      $entities[] = $entity;
    }
    //Return the entities
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    //If the primary submit button was clicked to select assets
    if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      //The form input uses checkboxes which returns zero for unchecked assets.  Remove these unchecked assets
      $assets = array_filter($form_state->getValue('assets'));
      //Get the cardinality for the media field that is being populated
      $field_cardinality = $form_state->get(['entity_browser', 'validators', 'cardinality', 'cardinality']);
      //If the field cardinality is limited and the number of assets selected is greater than the field cardinality
      if($field_cardinality > 0 && count($assets) > $field_cardinality){
        //Format the error message for singular or plural depending on cardinality
        $message = $this->formatPlural($field_cardinality, 'You can not select more than 1 entity.', 'You can not select more than @count entities.');
        //Set the error message on the form
        $form_state->setError($form['widget']['asset-container']['assets'], $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $assets = [];
    if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      $assets = $this->prepareEntities($form,$form_state);
    }
    $this->selectEntities($assets, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'submit_text' => $this->t('Select assets'),
      ] +
      parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   *
   * TODO: Add more settings for configuring this widget
   *
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    //Start with parent form
    $form = parent::buildConfigurationForm($form, $form_state);
    //Get list of media bundles
    $media_bundle_info = $this->entity_type_bundle_info->getBundleInfo('media');
    //Load media bundles
    $media_bundles = $this->entityTypeManager->getStorage('media_bundle')->loadMultiple(array_keys(($media_bundle_info)));
    //Filter out bundles that do not have type = webdam_asset
    $webdam_bundles = array_map( function($item){
        return $item->label;
      },array_filter($media_bundles, function($item){
        return $item->type == 'webdam_asset';
      })
    );
    //Add bundle dropdown to form
    $form['bundle'] = [
//      '#type' => 'container',
//      'select' => [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => $webdam_bundles,
//      ],
//      '#attributes' => ['id' => 'bundle-wrapper-' . $this->uuid()],
    ];
    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues()['table'][$this->uuid()]['form'];
    $this->configuration['bundle'] = $values['bundle'];
  }

  /**
   * Format display of one asset in media browser.
   *
   * @var \Drupal\media_webdam\Webdam $webdamAsset
   *
   * @return string
   */
  public function layoutMediaEntity($webdamAsset) {
    $assetName = $webdamAsset->name;
    if (!empty($webdamAsset->thumbnailurls)) {
      $thumbnail = '<div class="webdam-asset-thumb"><img src="' . $webdamAsset->thumbnailurls[2]->url . '" alt="' . $assetName . '" /></div>';
    } else {
      $thumbnail = '<span class="webdam-browser-empty">No preview available.</span>';
    }
    $element = '<div class="webdam-asset-checkbox">' . $thumbnail . '<p class="webdam-asset-filename">' . $assetName . '</p><a href="/webdam/asset/' . $webdamAsset->id . '" class="use-ajax" data-dialog-type="modal">Details</a></div>';
    return $element;
  }
}
