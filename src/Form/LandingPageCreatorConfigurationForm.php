<?php
/*
 *
 * @file
 * Contains \Drupal\landing_page_creator\Form\LandingPageCreatorConfigurationForm
 *
 * Form for Landing Page Creator Admin Configuration
 *
 **/
namespace Drupal\landing_page_creator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;

/*
 *  * Class ConfigurationForm.
 *
 *  {@inheritdoc}
 *
 *   */
class LandingPageCreatorConfigurationForm extends ConfigFormBase {

  /*
   * {@inheritdoc}
  */
  protected function getEditableConfigNames() {
    return [
      'landing_page_creator.configuration',
      ];
  }

  /*
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'landing_page_creator.admin_config_form';
  }

  /*
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('landing_page_creator.configuration');
    $form = array();

    $form['#prefix']  = '<h2>DataCite Administration</h2>';

    $form['username_datacite'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter username'),
      '#description' => t("the name of the user"),
    );

    $form['pass_datacite'] = array(
      '#type' => 'password',
      '#title' => t('Enter password'),
      '#description' => t("the password of the user"),
    );

    $form['prefix_datacite'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter prefix'),
      '#description' => t("the prefix for the account"),
    );

  // environment
    $form['url_datacite'] = array(
      '#type' => 'select',
      '#options' => array(
      'test.' => t('test'),
      '' => t('operational'),
      ),
      '#title' => t('Environment'),
      '#description' => t("Select test or operational environment"),
    );

    return parent::buildForm($form, $form_state);
 }

  /*
   * {@inheritdoc}
   *
   * NOTE: Implement form validation here
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //get user and pass from admin configuration
    $datacite_user = $form_state->getValue('username_datacite');
    $datacite_pass = $form_state->getValue('pass_datacite');
    $datacite_prefix = $form_state->getValue('prefix_datacite');

    if (!isset($datacite_user) || $datacite_user == ''){
       $form_state->setErrorByName('landing_page_creator', t('You are connecting to DataCite to obtain a DOI. <br>
                             Configure your Datacite credentials in the configuration interface'));
    }
    if (!isset($datacite_pass) || $datacite_pass == ''){
       $form_state->setErrorByName('landing_page_creator', t('You are connecting to DataCite to obtain a DOI. <br>
                             Configure your Datacite credentials in the configuration interface'));
    }
    if (!isset($datacite_prefix) || $datacite_prefix == ''){
       $form_state->setErrorByName('landing_page_creator', t('You are connecting to DataCite to obtain a DOI. <br>
                             Configure your Datacite credentials in the configuration interface'));
    }

  }

  /*
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /**
     * Save the configuration
    */
    $this->config('landing_page_creator.configuration')
      ->set('username_datacite', $form_state->getValue('username_datacite'))
      ->save();

    $this->config('landing_page_creator.configuration')
      ->set('pass_datacite', $form_state->getValue('pass_datacite'))
      ->save();

    $this->config('landing_page_creator.configuration')
      ->set('prefix_datacite', $form_state->getValue('prefix_datacite'))
      ->save();

    $this->config('landing_page_creator.configuration')
      ->set('url_datacite', $form_state->getValue('url_datacite'))
      ->save();
  }
}
?>
