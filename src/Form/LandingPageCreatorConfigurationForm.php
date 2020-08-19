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
      'landing_page_creator.confiugration',
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
    $config = $this->config('landing_page_creator.confiugration');
    $form[''] =;

    return parent::buildForm($form, $form_state);
 }

  /*
   * {@inheritdoc}
   *
   * NOTE: Implement form validation here
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /*
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /**
     * Save the configuration
    */
  }
}
?>
