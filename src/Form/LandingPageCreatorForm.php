<?php
/*
 * @file
 * Contains \Drupal\landing_page_creator/LandingPageCreatorForm
 *
 * This form will upload a MMD file and create landig page with doi
 *
 */

namespace Drupal\landing_page_creator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Link;

/*
 * {@inheritdoc}
 * Form class for the bokeh init form
 */
class LandingPageCreatorForm extends FormBase {
 /*
  * Returns a unique string identifying the form.
  *
  * The returned ID should be a unique string that can be a valid PHP function
  * name, since it's used in hook implementation names such as
  * hook_form_FORM_ID_alter().
  *
  * @return string
  *   The unique string identifying the form.
  *
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'landing_page_creator_form';
  }

 /*
  * @param $form
  * @param $form_state
  *
  * @return mixed
  *
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {

  /**
  * Build the form h.aggregator_remove
  */
  }

  /*
   * {@inheritdoc}
   * TODO: Impletment form validation here
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }
 	/*
   * {@inheritdoc}
   * Redirect init form to plot
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
     * Submit the form and do some actions
     */
  }
}
