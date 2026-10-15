<?php

namespace Drupal\landing_page_creator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Form for uploading mmd and convert to datacite.
 *
 * Create a temporary landing page and redirect to the registration form.
 *
 * {@inheritdoc}
 */
class LandingPageCreatorForm extends FormBase {

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->filesystem = $container->get('file_system');
    $instance->tempstore = $container->get('tempstore.private');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->streamWrapper = $container->get('stream_wrapper_manager');
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
    return 'landing_page_creator_form';
  }

  /**
   * Build the form.
   *
   * @param array $form
   *   The form array object.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return mixed
   *
   *   {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('landing_page_creator.configuration');
    $upload_message = $config->get('message_upload');
    $debug_message = $config->get('message_debug');
    /* Build the form */
    if ($config->get('debug')) {
      $form['debug'] = [
        '#type' => 'markup',
        '#format' => 'html',
        '#markup' => $this->t('@msg', ['@msg' => $debug_message]),
      ];
    }

    $form['creation'] = [
      '#type' => 'markup',
      '#format' => 'html',
      '#markup' => $this->t('@msg', ['@msg' => $upload_message]),
    ];

    // Upload the xml.
    $form['xml_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File upload'),
      '#upload_validators' => ['file_validate_extensions' => ['xml']],
      '#upload_location' => 'public://landingpage_xml',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Validate the form.
   *
   * @todo Impletment form validation here
   *
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get the stored datacite config.
    $config = $this->config('landing_page_creator.configuration');

    $datacite_user = $config->get('username_datacite');
    $datacite_pass = $config->get('pass_datacite');
    $datacite_prefix = $config->get('prefix_datacite');
    // $datacite_url = $config->get('url_datacite');
    if (!isset($datacite_user) || $datacite_user == '') {
      $form_state->setErrorByName('landing_page_creator', $this->t('You are connecting to DataCite to obtain a DOI. <br>
                             Configure your Datacite credentials$this-> in the configuration interface'));
    }
    if (!isset($datacite_pass) || $datacite_pass == '') {
      $form_state->setErrorByName('landing_page_creator', $this->t('You are connecting to DataCite to obtain a DOI. <br>
                             Configure your Datacite credentials in the configuration interface'));
    }
    if (!isset($datacite_prefix) || $datacite_prefix == '') {
      $form_state->setErrorByName('landing_page_creator', $this->t('You are connecting to DataCite to obtain a DOI. <br>
                             Configure your Datacite credentials in the configuration interface'));
    }
  }

  /**
   * Submit the form.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
     * Submit the form and do some actions
     */
    $tempstore = $this->tempstore->get('landing_page_creator');
    $user = $this->currentUser();

    // Uploaded file with mmd specifications.
    $form_file = $form_state->getValue('xml_file', FALSE);
    if (isset($form_file[0]) && !empty($form_file[0])) {
      $file = $this->entityTypeManager->getStorage('file')->load($form_file[0]);
      $file->save();
    }
    $furi = $file->getFileUri();
    // Translate mmd.xml to datacite.xml
    // should the repository included in this module?
    $furi_rp = $this->filesystem->realpath($furi);
    // Requires xsl file from repo.
    exec('xsltproc ' . drupal_get_path('module', 'landing_page_creator') . '/includes/mmd-to-datacite.xsl ' . $furi_rp . ' 2>&1', $datacite_metadata, $status);
    /* Temporary save the transformed datacite xml. */
    $xml = implode(" ", $datacite_metadata);
    $tempstore->set('datacite_xml', $xml);
    // Get the file contents.
    $xml_content = file_get_contents($furi);

    $dom = new \DOMDocument();
    $dom->loadXML($xml_content);
    foreach ($dom->getElementsByTagName('title') as $node) {
      if ($node->getAttribute('xml:lang') === 'en') {
        $title_en = $node->nodeValue;
      }
    }

    foreach ($dom->getElementsByTagName('abstract') as $node) {
      if ($node->getAttribute('xml:lang') === 'en') {
        $abstract_en = $node->nodeValue;
      }
    }

    // Get xml object iterator.
    // Problem with boolean.
    $xml = new \SimpleXMLIterator($xml_content);
    // dpm($xml_content);
    $ns = $xml->getNamespaces(TRUE);
    // $xml_wns = $xml->children(['mmd'])
    if (isset($ns['mmd'])) {
      $xml_wns = $xml->children($ns['mmd']);
    }
    else {
      $xml_wns = $xml->children();
    }

    $metadata_arr = $this->depth_mmd("", $xml_wns);
    $form_state->setValue('metadata', $metadata_arr);
    // dpm($metadata_arr);
    // personnel
    $count_personnel = 0;
    $role_list = [];
    $name_list = [];
    $email_list = [];
    $inst_list = [];

    // Isotopic categories.
    $count_itc = 0;
    $itc_list = [];

    // Data access type & resource.
    $count_access = 0;
    $dat_list = [];
    $dar_list = [];

    // Isotopic categories.
    $count_itc = 0;
    $itc_list = [];

    foreach ($metadata_arr as &$v) {
      if ($v[0] == 'metadata_identifier') {
        $metadata_id = $v[1];
      }
      if ($v[0] == 'title') {
        $title = $v[1];
      }
      if ($v[0] == 'abstract') {
        $abstract = $v[1];
      }
      if ($v[0] == 'temporal_extent start_date') {
        $start_date = $v[1];
      }
      if ($v[0] == 'temporal_extent end_date') {
        $end_date = $v[1];
      }

      if ($v[0] == 'geographic_extent rectangle north') {
        $north = $v[1];
      }
      if ($v[0] == 'geographic_extent rectangle south') {
        $south = $v[1];
      }
      if ($v[0] == 'geographic_extent rectangle east') {
        $east = $v[1];
      }
      if ($v[0] == 'geographic_extent rectangle west') {
        $west = $v[1];
      }

      // Start personnel.
      if ($v[0] == 'personnel role') {
        $role_list[$count_personnel] = $v[1];
      }
      if ($v[0] == 'personnel name') {
        $name_list[$count_personnel] = $v[1];
      }
      if ($v[0] == 'personnel email') {
        $email_list[$count_personnel] = $v[1];
      }
      if ($v[0] == 'personnel organisation') {
        $inst_list[$count_personnel] = $v[1];
      }
      if ($v[0] == 'personnel') {
        // Make sure the arrays are not empty when field is missing.
        if (!isset($role_list[$count_personnel])) {
          $role_list[$count_personnel] = NULL;
        }
        if (!isset($name_list[$count_personnel])) {
          $name_list[$count_personnel] = NULL;
        }
        if (!isset($email_list[$count_personnel])) {
          $email_list[$count_personnel] = NULL;
        }
        if (!isset($inst_list[$count_personnel])) {
          $inst_list[$count_personnel] = NULL;
        }
        $count_personnel = $count_personnel + 1;
      }

      // Start data access.
      if ($v[0] == 'data_access type') {
        $dat_list[$count_access] = $v[1];
      }
      if ($v[0] == 'data_access resource') {
        $dar_list[$count_access] = $v[1];
      }
      if ($v[0] == 'data_access') {
        // Make sure the arrays are not empty when field is missing.
        if (!isset($dat_list[$count_access])) {
          $dat_list[$count_access] = NULL;
        }
        if (!isset($dar_list[$count_access])) {
          $dar_list[$count_access] = NULL;
        }
        $count_access = $count_access + 1;
      }

      // Isotopic categories.
      if ($v[0] == 'iso_topic_category') {
        $itc_list[$count_itc] = $v[1];
        $count_itc = $count_itc + 1;
      }

      // Dataset author title, publication year and publisher.
      if ($v[0] == 'dataset_citation author') {
        $cit_auth = $v[1];
      }
      if ($v[0] == 'dataset_citation title') {
        $cit_tit = $v[1];
      }
      if ($v[0] == 'dataset_citation publication_date') {
        $cit_rdate = $v[1];
      }
      if ($v[0] == 'dataset_citation publisher') {
        $cit_publ = $v[1];
      }
      // License.
      if ($v[0] == 'use_constraint identifier') {
        $license = $v[1];
      }
      if (!isset($license)) {
        $license = NULL;
      }
    }

    // Define landing page node
    // Use english title if available.
    if (isset($title_en)) {
      $title = $title_en;
    }
    $node = Node::create([
     // The node entity bundle.
      'type' => 'landing_page',
      'langcode' => "en",
      'title' => $title,
      'uid' => $user->id(),
      'status' => 1,
      'promote' => 0,
      'comment' => 0,
    // 'path' => [
    // 'alias' => '/datasets/' . $doi,
    // 'pathauto' => PathautoState::SKIP,
    // ],
    ]);

    // Fill in the landing page node with content extracted
    // from mmd and datacite response.
    // Abstract
    // Use english abstract if available
    // $node->field_abstract[$node->language][]['value'] = $abstract;.
    if (isset($abstract_en)) {
      $node->set('field_abstract', $abstract_en);
    }
    else {
      $node->set('field_abstract', $abstract);
    }
    // Iso topic categories (can be multiple)
    for ($cn = 0; $cn < $count_itc; $cn++) {
      // $node->field_iso_topic_category[$node->language][$cn]['value'] = $itc_list[$cn];
      $node->set('field_iso_topic_category', $itc_list[$cn]);
    }

    // Citation.
    if (!isset($cit_auth) || $cit_auth == '') {
      if (!isset($cit_tit) || $cit_tit == '') {
        $cit_string = $title . ', (' . date('Y', strtotime($cit_rdate)) . ') published by ' . $cit_publ . '. ';
      }
      else {
        $cit_string = $cit_tit . ', (' . date('Y', strtotime($cit_rdate)) . ') published by ' . $cit_publ . '. ';
      }
    }
    else {
      if (!isset($cit_tit) || $cit_tit == '') {
        $cit_string = $cit_auth . ', ' . $title . ', (' . date('Y', strtotime($cit_rdate)) . ') published by ' . $cit_publ . '. ';
      }
      else {
        $cit_string = $cit_auth . ', ' . $cit_tit . ', (' . date('Y', strtotime($cit_rdate)) . ') published by ' . $cit_publ . '. ';
      }
    }

    // $node->field_citation[$node->language][]['value'] = $cit_string;
    $node->set('field_citation', $cit_string);

    // $node->field_license[$node->language][0]['value'] = $license;
    $node->set('field_license', trim($license));

    // Contact (can be multiple)
    $hr = '';
    for ($cn = 0; $cn < $count_personnel; $cn++) {
      // $node->field_contact[$node->language][$cn] = array('value' => $hr.'<strong>Role: </strong>'.$role_list[$cn].'
      // <strong>Name: </strong>'.$name_list[$cn].'
      // <strong>email: </strong>'.$email_list[$cn].'
      // <strong>Institution: </strong>'.$inst_list[$cn],
      // 'format' => 'full_html',
      $node->set('field_contact', [
        'value' => $hr . '<p><strong>Role: </strong>' . $role_list[$cn] . '</p>
                                                              <p><strong>Name: </strong>' . $name_list[$cn] . '</p>
                                                              <p><strong>email: </strong>' . $email_list[$cn] . '</p>
                                                              <p><strong>Institution: </strong>' . $inst_list[$cn] . '</p>',
        'format' => 'full_html',
      ]);

      $hr = '<hr>';

    }

    // Date start and end
    // $node->field_start_date[$node->language][]['value'] = date('Y-m-d\TH:i:s', strtotime($start_date));
    $node->set('field_start_date', date('Y-m-d\TH:i:s', strtotime($start_date)));
    if ($end_date != '') {
      $node->set('field_end_date', date('Y-m-d\TH:i:s', strtotime($end_date)));
    }
    else {
      $node->set('field_end_date', NULL);
    }

    // Bounding box.

    $node->set('field_north', $north);
    $node->set('field_south', $south);
    $node->set('field_east', $east);
    $node->set('field_west', $west);
    /*
    $points = [
    'extent' => [
    'left' => $west,
    'top' => $north,
    'right' => $east,
    'bottom' => $south,
    ],
    ];
     */
    // $geo_value = \Drupal::service('geofield.wkt_generator')->WktBuildPolygon($points);

    // $node->field_bnds[$node->language][0] = $geofield;
    $node->set('field_bnds', "POLYGON(" . $west . " " . $north . "," . $east . " " . $north . "," . $east . " " . $south . ", " . $west . " " . $south . "," . $west . " " . $north . ")");
    // $node->set('field_bnds', $geofield);
    // Access (can be multiple)
    $hr = '';
    for ($cn = 0; $cn < $count_access; $cn++) {
      /*  $node->field_access[$node->language][$cn] = array('value' => $hr.'<strong>Type: </strong>'.$dat_list[$cn].'
      <strong>Resource: </strong><a href='.$dar_list[$cn].'>'.$dar_list[$cn].'</a>',
      'format' => 'full_html',
      ); */

      /* Add .html string to OPeNDAP url */
      if ($dat_list[$cn] === 'OPeNDAP') {
        $node->set('field_access', [
          'value' => $hr . '<p><strong>Type: </strong>' . $dat_list[$cn] . '</p>
                              <p><strong>Resource: </strong><a href="' . $dar_list[$cn] . '".html>' . $dar_list[$cn] . '</a></p>',
          'format' => 'full_html',
        ]);
      }
      $hr = '<hr>';

    }

    // $node->path['alias'] = "datasets/".$doi;

    // $node = node_submit($node); // Prepare node for saving
    // node_save($node);

    $node->setPublished(FALSE);
    // $node->save();

    // $url = Url::fromRoute('landing_page_creator.register_form', [ 'doi' => $doi]);
    $url = Url::fromRoute('landing_page_creator.register_form');

    // Save the node object in private tempstore to send it to the next field form.

    $tempstore->set('node', $node);
    $form_state->setRedirectUrl($url);

  }

  /**
   * Extract mmd to the last child.
   */
  public function depthMmd($prefix, $iterator) {
    $kv_a = [];
    $prefix_ = $prefix . ' ';
    if ($prefix == '') {
      $prefix_ = '';
    }
    foreach ($iterator as $k => $v) {
      if ($iterator->hasChildren()) {
        $kv_a = array_merge($kv_a, $this->depthMmd($prefix_ . $k, $v));
        if ($k == 'personnel' || $k == 'data_access') {
          $kv_a[] = [$k];
        }
      }
      else {
        // Add mmd keys and values to form_state.
        $kv_a[] = [$prefix_ . $k, (string) $v];
      }
    }
    // This function returns an array of arrys.
    return $kv_a;
  }

  /**
   * Create xPath.
   */
  public function sxiToXpath($sxi, $key = NULL, &$tmp = NULL) {
    $keys_arr = [];
    // Get the keys count array.
    for ($sxi->rewind(); $sxi->valid(); $sxi->next()) {
      $sk = $sxi->key();
      if (array_key_exists($sk, $keys_arr)) {
        $keys_arr[$sk] += 1;
        $keys_arr[$sk] = $keys_arr[$sk];
      }
      else {
        $keys_arr[$sk] = 1;
      }
    }
    // Create the xpath.
    for ($sxi->rewind(); $sxi->valid(); $sxi->next()) {
      $sk = $sxi->key();
      if (!isset($$sk)) {
        $$sk = 1;
      }
      if ($keys_arr[$sk] >= 1) {
        $spk = $sk . '[' . $$sk . ']';
        $keys_arr[$sk] = $keys_arr[$sk] - 1;
        $$sk++;
      }
      else {
        $spk = $sk;
      }
      $kp = $key ? $key . '/' . $spk : '/' . $sxi->getName() . '/' . $spk;
      if ($sxi->hasChildren()) {
        $this->sxiToXpath($sxi->getChildren(), $kp, $tmp);
      }
      else {
        $tmp[$kp] = strval($sxi->current());
      }
      $at = $sxi->current()->attributes();
      if ($at) {
        $tmp_kp = $kp;
        foreach ($at as $k => $v) {
          $kp .= '/@' . $k;
          $tmp[$kp] = $v;
          $kp = $tmp_kp;
        }
      }
    }
    return $tmp;
  }

  /**
   * SimpleXmlIterator wrapper for xPath.
   */
  public function xmlToXpath($xml) {
    $sxi = new \SimpleXmlIterator($xml);
    return $this->sxiToXpath($sxi);
  }

}
