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
use Drupal\Core\Link;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/*
 * {@inheritdoc}
 * Form class for the bokeh init form
 */
class LandingPageCreatorRegisterForm extends FormBase {
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
    return 'landing_page_creator_register_form';
  }

 /*
  * @param $form
  * @param $form_state
  *
  * @return mixed
  *
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state, $nodeid = NULL, Request $request = NULL) {
    $config = \Drupal::config('landing_page_creator.configuration');
    $register_message = $config->get('message_register');
    $view_mode_config = $config->get('view_mode');

    $doi = $request->query->all()['doi'];
    $nid = $nodeid;

    //

    $entity_type = 'node';
    $view_mode = $view_mode_config; //from configuratorn

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $node = $storage->load($nid);
    //$node = \Drupal\node\Entity\Node::load($nodeid);
    $build = $view_builder->view($node, $view_mode);
    $output = render($build);
  /**
  * Build the form
  */
  $form['message'] = array(
    '#type' => 'markup',
    '#format' => 'html',
    '#markup' => t($register_message, ['@doi' => $doi]),
  );

  $form['actions'] = [
    '#type' => 'actions',
  ];


  $form['actions']['Register'] = [
    '#type' => 'submit',
    '#value' => t('Register'),
  ];

  $form['actions']['Discard'] = [
      '#type' => 'submit',
      '#value' => t('Discard'),
      '#submit' => array('::discardLandingPage'),
//      '#ajax' => [
//        'callback' => [$this, 'discardLandingPage'],
//        'event' => 'click',
//      ],
    ];

  $form['dataset'] = [
    '#type' => 'markup',
    '#markup' => $output,
  ];

  $form['doi'] = [
    '#type' => 'hidden',
    '#value' => $doi,
  ];

  $form['nodeid'] = [
    '#type' => 'hidden',
    '#value' => $nodeid,
  ];

  return $form;
  }

  /*
   * {@inheritdoc}
   * TODO: Impletment form validation here
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Implement form validation here if required

  }
 	/*
   * {@inheritdoc}
   * Redirect init form to plot
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      \Drupal::logger('landing_page_creator')->debug('Executing submitForm handler');
    /*
     * Submit the form and do some actions
     */
     $user = \Drupal::currentUser();
     global $base_url;
     global $base_path;

     // Get the stored datacite config
     $config = \Drupal::config('landing_page_creator.configuration');

     $datacite_user = $config->get('username_datacite');
     $datacite_pass = $config->get('pass_datacite');
     $datacite_prefix = $config->get('prefix_datacite');
     $datacite_url = $config->get('url_datacite');

     //Set the current landing page to published and save
     $entity_type = 'node';
     $nid = $form_state->getValue('nodeid');
     $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
     $node = $storage->load($nid);

     $node->setPublished(TRUE);


   //register the url to datacite
   //curl -H "Content-Type:text/plain;charset=UTF-8" -X PUT --user username:password -d "$(printf 'doi=10.5438/JQX3-61AT\nurl=http://example.org/')" https://mds.test.datacite.org/doi/10.5438/JQX3-61AT

    $doi = $form_state->getValue('doi');
     $body_content = 'doi='.$doi."\nurl=".$base_url.\Drupal::service('path.alias_manager')->getAliasByPath('/datasets/' . $doi);
     //dpm($body_content);
     $options = [
      'connect_timeout' => 30,
      'debug' => false,
      'auth' => [$datacite_user, $datacite_pass],
      'body' => $body_content,
      'headers' => array(
        'Content-Type' => 'text/plain;charset=UTF-8',
      )];
     //$client = \Drupal::httpClient();
     $result_reg = NULL;
     $client = new Client();
     $url = 'https://mds.'.$datacite_url.'datacite.org/doi/' .$doi;

 /**
  * TODO: Register web call commentet out for now due to testing...Will need to remove
  * comments and switch $message statement when going in prod
  */
     try {
      //$client = \Drupal::httpClient();
      $result_reg = $client->put($url, $options);

   }
   catch (RequestException $e){
     // Log the error.
     watchdog_exception('landing_page_creator', $e);
   }
    //dpm($result_reg);
    $status = $result_reg->getStatusCode();
    //$status = 201;
    //$result = $request->getBody();
    // $result_reg = drupal_http_request('https://mds.'.$datacite_url.'datacite.org/doi/'.$doi, $options_url);
    //$message = "Created landing page: <b>" .$title .'</b>, with node id ' . $node->id() . ' '. $result_reg->getReasonPhrase() .'!';
    $message = "Created landing page: <b>" . $node->getTitle() .'</b>, with node id ' . $node->id() . ' and registered DOI url: <strong>' . $url . '</strong>';
    $rendered_message = \Drupal\Core\Render\Markup::create($message);
    $status_message = new TranslatableMarkup ('@message', array('@message' => $rendered_message));
     //drupal_set_message( "Node with nid " . $node->nid . " saved!\n");


     //$url = \Drupal::url($routeName, $routeParameters);
     $path = '/node/' . $node->id();
     $alias = \Drupal::service('path.alias_manager')->getAliasByPath($path);

     $url = Url::fromUri('internal:' . $alias);
     //$url = Url::fromRoute('landing_page_creator.controller.confirm', [ 'nodeid' => $node->id(), 'doi' => $doi]);
     //$url = Url::fromRoute('landing_page_creator.register_form', [ 'nodeid' => $node->id(), 'doi' => $doi]);
     if($status != 201 )  {
       \Drupal::messenger()->addError('Something went wrong during DOI registration. Landing page not created! Please contact administrator');
       $url = Url::fromRoute('landing_page_creator.landing_page_creator_form');
       $node->delete();
       $form_state->setRedirectUrl($url);
     }
     else {
     //Save the node and print message
     $node->save();
     \Drupal::messenger()->addMessage($status_message);
     $form_state->setRedirectUrl($url);
   }
  }

  public function discardLandingPage(array &$form, FormStateInterface $form_state) {
      \Drupal::logger('landing_page_creator')->debug('Executing discardLandingPage ajax callback');
    //$response = new AjaxResponse();

    // Delete the node
    //Set the current landing page to published and save
    $nid = $form_state->getValue('nodeid');
    $entity_type = 'node';
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $node = $storage->load($nid);
    $node->delete();

    $url = Url::fromRoute('landing_page_creator.landing_page_creator_form');
    //$command = new RedirectCommand($url->toString());
    //$response->addCommand($command);
    \Drupal::messenger()->addMessage('Landing page with node id: '.$nid .' discarded. Please upload another dataset', 'warning');
    $form_state->setRedirectUrl($url);
    //return $response;
  }
}
