<?php
/*
 Copyright (c) Mondial-IT BV - Blue Marloc 2024
   Created on 2024-11-21 at 11:23:21
 */ /** @noinspection PhpUnused */

namespace Drupal\bm_cache\BMCacheForm;

use Drupal;
use Drupal\bm_cache\BMCache\BMCache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BMCacheForm extends FormBase {

  protected $configFactory;


  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;

  }

  public static function create(ContainerInterface $container): BMCacheForm|static {
    /* @var ConfigFactoryInterface $configFactory*/
    $configFactory = $container->get('config.factory');
    return new static($configFactory);
  }

  // Form build and submit functions go here.

  /**
   * @return string
   */
  public function getFormId(): string {
    return 'BMCacheForm';
    // TODO: Implement getFormId() method.
  }
  protected array $all_cids=[];

  /**
   * @throws Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['custom_styles'] = [
      '#type' => 'inline_template',
      '#template' => '<style>{{ style }}</style>',
      '#context' => [
        'style' => '
          #bmcacheform .cid{width:500px;}
          #bmcacheform .count{width:200px;}
          #bmcacheform .size{}
        ',
      ],
    ];

    $form['debug_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug'),
      '#description' => $this->t('Check this box to enable the feature.'),
      '#ajax' => [
        'callback' => '::exampleCheckboxAjaxCallback', // Method within this class
        'wrapper' => 'checkbox-result', // The HTML ID of the element to update with the AJAX response
        'effect' => 'fade', // Optional effect
      ],
    ];

    $form['result'] = [
      '#type' => 'markup',
      '#markup' => '<div id="checkbox-result"></div>',
    ];
    $input=$form_state->getUserInput();
    $debug =isset($input['debug_checkbox']);
    Drupal::messenger()->deleteAll();

    $form['markup'] = [
      '#type'=>'text',
      '#markup'=>'
            <br />
            <h1>Flushing Blue Marloc caches</h1>
            <div class="grid-fr-fr-fr gap-20">
          '
    ];

// cache tests
//    Drupal::messenger()->addStatus('getting temp cache');
//    $tempCache=Drupal::cache('bluemarloc')->get('BMDebug');
//    dsm($tempCache,'tempCache');
//
//    $tempCache=BMCache::cache()->get('BMDebug');
//    dsm($tempCache,'tempCache via BMCache');
//
//    $permCache=BMCache::cache('permanent')->get('test 1 cache entry');
//    dsm($permCache,'permCache');

    // BMCache temporary cache
    $form['title']=['#markup'=>'<div class="panel">
        <h3>Regular Blue Marloc cache</h3><p>`Cache clear all` clears this cache as well.</p>
        <p>These items can be deleted and will be recreated where they are needed.</p>'];

    // get regular drupal cache of blue marloc as a list [cid, count, size]
    $temporaryCache = BMCache::cache()->list();
    if($debug)dsm($temporaryCache,'temporary cache');
    $cids=[];
    // cids are combined with the sub cids in the temporary cache.
    // combine them into a cid entry
    $size=0;
    foreach ($temporaryCache as $row) {
      $parts = explode('.',$row['cid']);
      $this->all_cids[]=$row['cid'];
      if(!isset($groups[$parts[0]])){
        $groups[$parts[0]]=0;
        $size=0;
      }
      $groups[$parts[0]]++;
      $size+= $row['size'];
      $cids[$parts[0]] = ['cid'=>$parts[0],'count'=>$groups[$parts[0]],'size'=>$size];
    }
    $form['temporary_cache_items'] = array(
      '#type' => 'tableselect',
      '#header' => array(
        'cid'=>['data'=>'Cache ID','class'=>"cid"],
        'count'=>['data'=>'Count','class'=>'count'],
        'size'=>['data'=>'Size','class'=>'size'] ),
      '#options' => $cids,
      '#suffix'=>'</div>'
    );

    // add an item
    if($debug){
      Drupal::messenger()->addStatus('i added a bunch of entries to permanent cache for the test');
      BMCache::cache('permanent')->set('test cache entry',['test1','test2']);
      BMCache::cache('permanent')->set('test cache entry',['test3','test4']);
      BMCache::cache('permanent')->set('test 1 cache entry',['test5','test6']);
      BMCache::cache('permanent')->set('test 2 cache entry',['test7','test8']);
    }

    $form['title2']=['#markup'=>'<div class="panel">
        <h3>Permanent Blue Marloc cache</h3>
        <p>This is not cleared by `Cache clear all`</p>
        <p>Note: These items can be deleted, but like googleplaces, we prefer to maintain them.
            <br />When deleted they will be recreated by who needs them.</p>'];

    $permanentCache = BMCache::cache('permanent')->list();
    if($debug)dsm($permanentCache,'the permanent cache contents');
    $form['permanent_cache_items'] = array(
      '#type' => 'tableselect',
      '#header' => array(
        'cid' => ['data' => 'Cache ID', 'class' => 'cid'],
        'count' => ['data' => 'Count', 'class' => 'count'],
        'size' => ['data' => 'Size', 'class' => 'size']
      ),
      '#options' => $permanentCache,
      '#suffix'=>'</div></div>'
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete selected cache groups'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // in temporary cache, the cid and subCid are concatenated
    // to delete the cids we have to delete all the cid combinations separately.

    // Delete the selected cache items.
    $selected_items = $form_state->getValue('temporary_cache_items');
    foreach ($selected_items as $cache_id => $selected) {
      if ($selected) {
        foreach ($this->all_cids as $cid) {
          if (str_starts_with($cid, $cache_id)) {
            BMCache::cache('temporary')->delete($cid);
            Drupal::messenger()->addStatus($cache_id . ':' . $cid . ' deleted');
          }
        }
      }
    }

    $selected_items = $form_state->getValue('permanent_cache_items');
    dsm($selected_items,'selected items');
    foreach ($selected_items as $cache_id => $selected) {
      if ($selected) {
          BMCache::cache('permanent')->delete($cache_id);
          Drupal::messenger()->addStatus('['.$cache_id  . '] deleted');
       }
    }
  }

  /**
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return mixed
   */
  public function exampleCheckboxAjaxCallback(array &$form, FormStateInterface $form_state): mixed {
    // You can check the state of the checkbox and update the 'result' element accordingly.
    if ($form_state->getValue('debug_checkbox')) {
      $text = $this->t('Debug is enabled.');
    } else {
      $text = $this->t('Debug is disabled.');
    }

    // Update the 'result' element with new content based on the checkbox state.
    $form['result']['#markup'] = '<div id="checkbox-result">' . $text . '</div>';

    // Return the part of the form to be updated.
    return $form['result'];
  }
}
