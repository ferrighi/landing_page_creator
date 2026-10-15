<?php

namespace Drupal\landing_page_creator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\pathauto\PathautoState;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * This form create landingpage CT  with a registered DOI.
 *
 * {@inheritdoc}
 */
class LandingPageCreatorRegisterForm extends FormBase {
  /**
   * Filesystem service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $filesystem;

  /**
   * Tempstore private service.
   *
   * @var Drupal\Core\TempStore\PrivateTempStoreFactory
   */

  protected $tempstore;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $streamWrapper;

  /**
   * Path alias manager service.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected $pathAliasManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->filesystem = $container->get('file_system');
    $instance->tempstore = $container->get('tempstore.private');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->streamWrapper = $container->get('stream_wrapper_manager');
    $instance->pathaliasManager = $container->get('path_alias.manager');

    return $instance;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   *
   *   {@inheritdoc}
   */
  public function getFormId() {
    return 'landing_page_creator_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('landing_page_creator.configuration');
    $register_message = $config->get('message_register');
    $debug_message = $config->get('message_debug');
    $view_mode_config = $config->get('view_mode');

    $tempstore = $this->tempstore->get('landing_page_creator');
    $node = $tempstore->get('node');
    // dpm($node);
    $entity_type = 'node';
    // From configuratorn.
    $view_mode = $view_mode_config;

    $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);
    // $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    // $node = $storage->load($nid);
    // $node = \Drupal\node\Entity\Node::load($nodeid);
    $build = $view_builder->view($node, $view_mode);
    $output = render($build);
    // Dispaly debug warning.
    if ($config->get('debug')) {

      $form['debug'] = [
        '#type' => 'markup',
        '#format' => 'html',
        '#markup' => $this->t('@msg', ['@msg' => $debug_message]),
      ];
    }
    /*Build the form */
    $form['message'] = [
      '#type' => 'markup',
      '#format' => 'html',
      '#markup' => $this->t('@msg', ['@msg' => $register_message]),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['Register'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
    ];

    $form['actions']['Discard'] = [
      '#type' => 'submit',
      '#value' => $this->t('Discard'),
      '#submit' => ['::discardLandingPage'],
    ];

    $form['dataset'] = [
      '#type' => 'markup',
      '#markup' => $output,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getLogger('landing_page_creator')->debug('Executing submitForm handler');
    // Get the stored datacite config.
    $config = $this->config('landing_page_creator.configuration');

    $datacite_user = $config->get('username_datacite');
    $datacite_pass = $config->get('pass_datacite');
    $datacite_prefix = $config->get('prefix_datacite');
    $datacite_url = $config->get('url_datacite');

    $tempstore = $this->tempstore->get('landing_page_creator');
    $node = $tempstore->get('node');

    // Do dataite call here.
    $xml = $tempstore->get('datacite_xml');
    $options = [
      'timeout' => 30,
      'debug' => FALSE,
      'body' => $xml,
      'auth' => [$datacite_user, $datacite_pass],
      'headers' => [
        'Accept' => 'application/xml',
        'Content-Type' => 'application/xml;charset=UTF-8',
      ],
    ];
    $url = 'https://mds.' . $datacite_url . 'datacite.org/metadata/' . $datacite_prefix;
    $client = new Client();
    $response = NULL;
    try {
      $response = $client->post($url, $options);

    }
    catch (RequestException $e) {
      // Log the error.
      watchdog_exception('landing_page_creator', $e);
      $this->messenger()->addError($this->t('Datacite request has failed. Please check log messages and/or contact administrator'));
      $url = Url::fromRoute('landing_page_creator.landing_page_creator_form');
      // $form_state->setRedirectUrl($url);
      return new RedirectResponse($url->toString());
    }
    catch (ClientException $e) {
      // Log the error.
      watchdog_exception('landing_page_creator', $e);
      $this->messenger()->addError($this->t('Datacite request has failed. Please check log messages and/or contact administrator'));
      $url = Url::fromRoute('landing_page_creator.landing_page_creator_form');
      // $form_state->setRedirectUrl($url);
      return new RedirectResponse($url->toString());
    }

    $status = $response->getStatusCode();

    // $result = drupal_http_request('https://mds.'.$datacite_url.'datacite.org/metadata/'.$datacite_prefix, $options_md);
    // extract DOI from  http response
    if ($response != NULL && $status == 201) {
      $doi = explode("metadata/", $response->getHeader('Location')[0])[1];
      $this->getLogger('landing_page_creator')->debug('DataCite metadata submitted sucessfully. Got DOI: ' . $doi);
      if ($datacite_url == 'test.') {
        // Test env.
        $doi_uri = 'https://handle.test.datacite.org/' . $doi;
      }
      else {
        // Operational env.
        $doi_uri = 'https://doi.org/' . $doi;
      }
    }
    else {
      $this->messenger()->addError($this->t('Datacite request has failed'));
      $url = Url::fromRoute('landing_page_creator.landing_page_creator_form');
      return new RedirectResponse($url->toString());
    }

    $node->setPublished(TRUE);
    $node->set('path', ['alias' => '/datasets/' . $doi, 'pathauto' => PathautoState::SKIP]);
    $node->set('field_doi', $doi_uri);

    // $doi = $form_state->getValue('doi');
    $body_content = 'doi=' . $doi . "\nurl=" . $base_url . $this->pathAliasManager->getAliasByPath('/datasets/' . $doi);
    // dpm($body_content);
    $options = [
       'connect_timeout' => 30,
       'debug' => FALSE,
       'auth' => [$datacite_user, $datacite_pass],
       'body' => $body_content,
       'headers' => [
         'Content-Type' => 'text/plain;charset=UTF-8',
       ]
];
    // $client = \Drupal::httpClient();
    $result_reg = NULL;
    $client = new Client();
    $url = 'https://mds.' . $datacite_url . 'datacite.org/doi/' . $doi;

    if (!$config->get('debug')) {
      $this->getLogger('landing_page_creator')->debug('Register the DOI: ' . $doi);
      try {
        // $client = \Drupal::httpClient();
        $result_reg = $client->put($url, $options);

      }
      catch (RequestException $e) {
        // Log the error.
        watchdog_exception('landing_page_creator', $e);
      }
      // dpm($result_reg);
      $status = $result_reg->getStatusCode();
      $this->getLogger('landing_page_creator')->debug('DataCite DOI registration with status code: ' . $status);
      if ($status != 201) {
        \Drupal::messenger()->addError('Something went wrong during DOI registration. Landing page not created! Please contact administrator');
        $url = Url::fromRoute('landing_page_creator.landing_page_creator_form');
        // $node->delete();
        $tempstore->delete('node');
        $tempstore->delete('datacite_xml');
        $form_state->setRedirectUrl($url);
      }
    }
    else {
      $this->getLogger('landing_page_creator')->debug('Module is in debug mode. DOI registration skipped');
      $this->messenger()->addMessage('Module is in debug mode. DOI registration skipped', 'warning');
      $status = 201;

      $options = [
           'connect_timeout' => 30,
           'debug' => FALSE,
           'auth' => [$datacite_user, $datacite_pass],
          // 'body' => $body_content,
           'headers' => [
             'Content-Type' => 'application/plain;charset=UTF-8',
           ]
];
      // $client = \Drupal::httpClient();
      $result_reg = NULL;
      $client = new Client();
      $url = 'https://mds.' . $datacite_url . 'datacite.org/doi/' . $doi;

      $this->getLogger('landing_page_creator')->debug('Delete the DataCite draft with DOI: ' . $doi);
      try {
        // $client = \Drupal::httpClient();
        $result_reg = $client->delete($url, $options);

      }
      catch (RequestException $e) {
        // Log the error.
        watchdog_exception('landing_page_creator', $e);
      }
      // dpm($result_reg);
      $status = $result_reg->getStatusCode();
      if ($status != 200) {
        $this->messenger()->addMessage('Something went wrong deleting DataCite draft. Please see logs or contact Administrator', 'warning');
      }

    }

    // Else {
    // Save the node and print message.
    $node->save();
    $message = "Created landing page: <b>" . $node->getTitle() . '</b>, with node id ' . $node->id() . ' and registered DOI url: <strong>' . $url . '</strong>';
    $rendered_message = Markup::create($message);
    $status_message = new TranslatableMarkup('@message', ['@message' => $rendered_message]);
    $path = '/node/' . $node->id();
    $alias = $this->pathAliasManager->getAliasByPath($path);
    $url = Url::fromUri('internal:' . $alias);
    $tempstore->delete('node');
    $tempstore->delete('datacite_xml');
    $this->messenger()->addMessage($status_message);
    $form_state->setRedirectUrl($url);
    // }
  }

  /**
   * Discard and undo the created landingpage and doi.
   */
  public function discardLandingPage(array &$form, FormStateInterface $form_state) {
    $this->getLogger('landing_page_creator')->debug('Executing discardLandingPage');
    // $response = new AjaxResponse();
    $tempstore = $this->tempstore->get('landing_page_creator');
    $tempstore->delete('node');
    $tempstore->delete('datacite_xml');

    $url = Url::fromRoute('landing_page_creator.landing_page_creator_form');
    // $command = new RedirectCommand($url->toString());
    // $response->addCommand($command);
    $this->messenger()->addMessage('Landing page discarded. Please upload another dataset', 'warning');
    $form_state->setRedirectUrl($url);
    // Return $response;.
  }

}
