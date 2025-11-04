<?php

/*
 Copyright (c) Mondial-IT BV - Blue Marloc 2024
   Created on 2024-11-21 at 11:23:21
 */

namespace Drupal\bm_cache\Form;

//use Drupal\bm_live\Controller\BMLiveController;
//use Drupal\bm_taxonomies\BMCompanyCampaignCanvass\BMCompanyCampaignCanvass;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {


  public function getFormId(): string {
    return 'bm_live_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['intro'] = [
      '#type' => 'markup',
      '#markup' => '<h1>BM Cache</h1>
        <hr>
        <div class="grid-fr-fr-fr gap-20">'];

    $this->panel_css($form);
    $this->panel_js($form);
    $this->panel_code($form);

    $form['outro'] = [
      '#type' => 'markup',
      '#markup' => '</div>'];

    return parent::buildForm($form, $form_state);
  }
  private function panel_css(&$form): void {
    $form['panel_css']=[
      '#markup'=>'<div class="panel">
        <h3>CSS</h3>
        </div>'
    ];
  }
  private function panel_js(&$form): void {
    $form['panel_js']=[
      '#markup'=>'<div class="panel">
        <h3>Additional JS</h3>

        </div>'
    ];
  }

  private function panel_code(&$form): void {
    $form['panel_cache']=[
      '#markup'=>'<div class="panel">
        <h3>Cache</h3>
        <p>Blue Marloc maintains its private caches.</p>

        <p><b>Temporary cache (drupal controlled)</b><br />
        These are identical and use the Drupal cache method.
        <br />Cache clear all clears this cache
        <ul>
            <li>Drupal::cache(\'bluemarloc\')</li>
            <li>BMCache::cache(\'bluemarloc\')</li>
            <li>BMCache::cache(\'temporary\') </li>
            <li>BMCache::cache()</li>
        </ul>

        <p><b>Permanent cache (not controlled)</b><br />
        This is the permanent cache in table bluemarloc_cache</p>
            <ul><li>BMCache::cache(\'permanent\')</li></ul>
        </ul>
        <h4>Selective cash clear</h4>
        <p>The cashform enables clearing specific items from the caches</p>
        <ul>
        <li>See <a href="/admin/flush/bluemarloc_cache_form">cache menu</a>
        Page to selectively clear cache elements.</li>
        </ul>
        </p>
        </div>'
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {



    parent::submitForm($form, $form_state);
  }

  protected function getEditableConfigNames() {
    // TODO: Implement getEditableConfigNames() method.
  }

}
