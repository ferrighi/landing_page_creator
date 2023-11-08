<?php

namespace Drupal\landing_page_creator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigurationForm.
 *
 * {@inheritdoc}
 */
class LandingPageCreatorConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'landing_page_creator.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'landing_page_creator.admin_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('landing_page_creator.configuration');
    // $form = array();
    // form['#prefix']  = '<h2>DataCite Administration</h2>';.
    $form['datacite'] = [
      '#type' => 'fieldset',
      '#title' => 'Configure Datacite Account',
      '#tree' => TRUE,
    ];

    $form['datacite']['username_datacite'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter username'),
      '#description' => $this->t("the name of the user"),
      '#default_value' => $config->get('username_datacite'),
    ];

    $form['datacite']['pass_datacite'] = [
      '#type' => 'password',
      '#title' => $this->t('Enter password'),
      '#description' => $this->t("the password of the user"),
      '#default_value' => $config->get('pass_datacite'),
    ];

    $form['datacite']['prefix_datacite'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter prefix'),
      '#description' => $this->t("the prefix for the account"),
      '#default_value' => $config->get('prefix_datacite'),
    ];

    // Environment.
    $form['datacite']['url_datacite'] = [
      '#type' => 'select',
      '#options' => [
        'test.' => $this->t('test'),
        '' => $this->t('operational'),
      ],
      '#title' => $this->t('Environment'),
      '#description' => $this->t("Select test or operational environment"),
      '#default_value' => $config->get('url_datacite'),
    ];

    $form['register'] = [
      '#type' => 'fieldset',
      '#title' => 'Provides the posibility to disable DOI registration for debugging purposes',
      '#tree' => TRUE,
    ];
    $form['register']['debug'] = [
      '#type' => 'checkbox',
      '#title' => 'Check this to disable DOI Registration',
      '#default_value' => $config->get('debug'),
    ];

    // Choose view_mode for display landing page draft.
    $form['draft'] = [
      '#type' => 'fieldset',
      '#title' => 'Configure View mode for landing page draft',
      '#tree' => TRUE,
    ];
    $form['draft']['view_mode'] = [
      '#type' => 'select',
      '#options' => [
        'default' => $this->t('default'),
        'teaser' => $this->t('teaser'),
      ],
      '#title' => $this->t('Landing page draft view mode'),
      '#description' => $this->t("Select which content type view mode to use for displaying landing page draft"),
      '#default_value' => $config->get('view_mode'),
    ];

    // Configure standard messages.
    $form['messages'] = [
      '#type' => 'fieldset',
      '#title' => 'Configure Messages',
      '#tree' => TRUE,
    ];

    $form['messages']['upload_message'] = [
      '#type' => 'textarea',
      '#title' => 'Enter upload MMD file message here',
      '#default_value' => $config->get('message_upload'),
    ];
    $form['messages']['register_message'] = [
      '#type' => 'textarea',
      '#title' => 'Enter DOI registration message here',
      '#default_value' => $config->get('message_register'),
    ];

    $form['messages']['debug'] = [
      '#type' => 'textarea',
      '#title' => 'Enter DEBUG  message here',
      '#default_value' => $config->get('message_debug'),
    ];

    $form['#attached']['library'][] = 'landing_page_creator/landing_page_creator';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * NOTE: Implement form validation here.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get user and pass from admin configuration.
    $values = $form_state->getValues();
    $datacite_user = $values['datacite']['username_datacite'];
    $datacite_pass = $values['datacite']['pass_datacite'];
    $datacite_prefix = $values['datacite']['prefix_datacite'];

    if (!isset($datacite_user) || $datacite_user == '') {
      $form_state->setErrorByName('landing_page_creator', $this->t('You are connecting to DataCite to obtain a DOI. <br>
			     Configure your Datacite credentials in the configuration interface. <br>
				Username missing'));
    }
    if (!isset($datacite_pass) || $datacite_pass == '') {
      $form_state->setErrorByName('landing_page_creator', $this->t('You are connecting to DataCite to obtain a DOI. <br>
        Configure your Datacite credentials in the configuration interface. <br>
        Password missing'));
    }
    if (!isset($datacite_prefix) || $datacite_prefix == '') {
      $form_state->setErrorByName('landing_page_creator', $this->t('You are connecting to DataCite to obtain a DOI. <br>
			     Configure your Datacite credentials in the configuration interface. <br>
				Prefix missing'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /* Save the configuration */
    $values = $form_state->getValues();
    // Check if the password field have changed.
    if ($values['datacite']['pass_datacite'] != "" ||  $values['datacite']['pass_datacite'] != NULL) {
      $this->configFactory->getEditable('landing_page_creator.configuration')
        ->set('pass_datacite', $values['datacite']['pass_datacite'])
        ->save();
    }
    $this->configFactory->getEditable('landing_page_creator.configuration')
      ->set('username_datacite', $values['datacite']['username_datacite'])
      ->set('prefix_datacite', $values['datacite']['prefix_datacite'])
      ->set('url_datacite', $values['datacite']['url_datacite'])
      ->set('message_upload', $values['messages']['upload_message'])
      ->set('message_register', $values['messages']['register_message'])
      ->set('message_debug', $values['messages']['debug'])
      ->set('view_mode', $values['draft']['view_mode'])
      ->set('debug', $values['register']['debug'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
