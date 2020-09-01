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
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection;
use Drupal\pathauto\PathautoState;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\serialization\Encoder\XmlEncoder;
use XSLTProcessor;
use SimpleXMLElement;
use \Drupal\Core\StringTranslation\TranslatableMarkup;
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
  * Build the form
  */
  $form['creation'] = array(
    '#type' => 'markup',
    '#format' => 'html',
    '#markup' => t('Here you create a landing page for your dataset and automatically assign a DOI using the METNO Datacite account. <br>
                    Upload your xml, with mmd specifications according to <a href="https://github.com/steingod/mmd/blob/master/doc/mmd-specification.pdf">the METNO Metatdata Description</a>. <br><br>
                    <span style="color:red; font-weight:bold">This is a one-step process: once you submit the form a DOI will be created and cannot be deleted.</span>'),
  );

  // upload the xml
  $form['xml_file'] = array(
    '#type' => 'managed_file',
    '#title' => t('File upload'),
    '#upload_validators' => array('file_validate_extensions' => array('xml'),),
    '#upload_location' => 'public://landingpage_xml',
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );


  return $form;
  }

  /*
   * {@inheritdoc}
   * TODO: Impletment form validation here
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get the stored datacite config
  /*  $config = \Drupal::config('landing_page_creator.confiugration');

    $datacite_user = $config->get('username_datacite');
    $datacite_pass = $config->get('pass_datacite');
    $datacite_prefix = $config->get('prefix_datacite');
    $datacite_url = $config->get('url_datacite');

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
    }*/
  }
 	/*
   * {@inheritdoc}
   * Redirect init form to plot
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
     * Submit the form and do some actions
     */
     $user = \Drupal::currentUser();
     global $base_url;
     global $base_path;

     // uploaded file with mmd specifications
     //$furi = $form_state->getValue('xml_file', FALSE); //["#file"]->uri;
    //dpm($furi);
    $form_file = $form_state->getValue('xml_file', FALSE);
    if (isset($form_file[0]) && !empty($form_file[0])) {
      $file = File::load($form_file[0]);
      $file->save();
    }
    $furi = $file->getFileUri();
     // translate mmd.xml to datacite.xml
     // should the repository included in this module?
     $furi_rp = \Drupal::service('file_system')->realpath($furi);
     exec('xsltproc '.drupal_get_path('module', 'landing_page_creator').'/includes/mmd-to-datacite.xsl '.$furi_rp.' 2>&1', $datacite_metadata, $status); //requires xsl file from repo
     //$xml_file = file_get_contents($furi_rp);
     //$xslt_file = file_get_contents(drupal_get_path('module', 'landing_page_creator').'/includes/mmd-to-datacite.xsl');
     //$xslt = new XSLTProcessor();
     //$xslt->importStylesheet(new SimpleXMLElement($xslt_file));
     //$parent = XmlEncoder::encode($xml_file, 'xml', 'mmd');
     //$datacite_metadata = $xslt->transformToXml(new SimpleXMLElement($parent));

     // Get the stored datacite config
     $config = \Drupal::config('landing_page_creator.confiugration');

     $datacite_user = $config->get('username_datacite');
     $datacite_pass = $config->get('pass_datacite');
     $datacite_prefix = $config->get('prefix_datacite');
     $datacite_url = $config->get('url_datacite');

     // send metadata to datacite
     //curl -H "Content-Type: application/xml;charset=UTF-8" -X POST -i --user username:password -d @$datacite_metadata.xml https://mds.test.datacite.org/metadata

/*     $xml = implode(" ",$datacite_metadata);
     $options_md = array(
                 'method' => 'POST',
                 'data' => $xml,
                 'timeout' => 25,
                 'headers' => array('Content-Type' => 'application/xml;charset=UTF-8',
                                    'Authorization' => 'Basic ' . base64_encode($datacite_user . (":" . $datacite_pass)),),
     );
     $options = [
      'connect_timeout' => 30,
      'debug' => false,
      'body' => $xml,
      'headers' => array(
        'Accept' => 'application/xml',
        'Content-Type' => 'application/xml;charset=UTF-8',
      )];
     //$client = \Drupal::httpClient();
     $result = NULL;
  /*   $url = 'https://mds.'.$datacite_url.'datacite.org/metadata/'.$datacite_prefix;
     try {
      $client = \Drupal::httpClient();
      $request = $client->request('POST',$url,$options);

    }
    catch (RequestException $e){
      // Log the error.
      watchdog_exception('custom_modulename', $e);
    }

    $status =  $resquest->getStatusCode();
    $result = $request->getBody();
/*
    $request = $client->createRequest(array('GET', $uri));
    $result = NULL;
    try {
      $response = $client->get($uri);
      $result = $response->getBody();
    }
    catch (RequestException $e) {
      watchdog_exception('metsis_lib', $e);
    }*/
//     $result = drupal_http_request('https://mds.'.$datacite_url.'datacite.org/metadata/'.$datacite_prefix, $options_md);
     //extract DOI from  http response
/*     if ($result != NULL && $status == 201) {
        $doi = explode("metadata/", $result->headers['location'])[1];
        if ($datacite_url == 'test.') {
           $doi_uri = 'https://handle.test.datacite.org/'.$doi; //test env.
        }else{
           $doi_uri = 'https://doi.org/'.$doi; // operational env.
        }
     }else{
        \Drupal::messenger()->addError(t('Datacite request has failed'));
     } */
     $doi_url = "https://doi.example.com/" . uniqid();

     // For static DOI testing
     $doi = bin2hex(random_bytes(5)) . '/' . bin2hex(random_bytes(6));
     //citation becomes:
     //Creator (PublicationYear): Title. Version. Publisher. (resourceTypeGeneral). Identifier
      //\Drupal::messenger()->message('', $furi);
    // $xml_content = file_get_contents($furi); // this is a string from gettype
    $xml_content = file_get_contents($furi); // this is a string from gettype
    //$xml_path = $this->xmlToXpath($xml_content);
    //dpm($xml_path);
    $dom =new \DOMDocument;
    $dom->loadXML ($xml_content);
    foreach( $dom->getElementsByTagName( 'title' ) as $node ) {
      if( $node->getAttribute( 'xml:lang' ) === 'en') {
        $title_en = $node->nodeValue;
      }
    }

    foreach( $dom->getElementsByTagName( 'abstract' ) as $node ) {
      if( $node->getAttribute( 'xml:lang' ) === 'en') {
        $abstract_en = $node->nodeValue;
      }
    }
    //dpm($title_en);
    ////get xml object iterator
    $xml = new \SimpleXMLIterator($xml_content); // problem with boolean
    //dpm($xml_content);
    $ns = $xml->getNamespaces(true);
    //$xml_wns = $xml->children(['mmd'])
    if(isset($ns['mmd'])) {
      $xml_wns = $xml->children($ns['mmd']);
    }
    else {
      $xml_wns = $xml->children();
    }
    //dpm($xml_wns);
    $metadata_arr = $this->depth_mmd("", $xml_wns);
    $form_state->setValue('metadata', $metadata_arr);
    //dpm($metadata_arr);
    // personnel
    $count_personnel = 0;
    $role_list = array();
    $name_list = array();
    $email_list = array();
    $inst_list = array();

    // isotopic categories
    $count_itc = 0;
    $itc_list = array();

    // data access type & resource
    $count_access = 0;
    $dat_list = array();
    $dar_list = array();

    // isotopic categories
    $count_itc = 0;
    $itc_list = array();

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

      // start personnel
      if ($v[0] == 'personnel role'){
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
        // make sure the arrays are not empty when field is missing
        if (!isset($role_list[$count_personnel])) {
          $role_list[$count_personnel] = null;
        }
        if (!isset($name_list[$count_personnel])) {
          $name_list[$count_personnel] = null;
        }
        if (!isset($email_list[$count_personnel])) {
          $email_list[$count_personnel] = null;
        }
        if (!isset($inst_list[$count_personnel])) {
          $inst_list[$count_personnel] = null;
        }
        $count_personnel = $count_personnel + 1;
      }

      // start data access
      if ($v[0] == 'data_access type'){
        $dat_list[$count_access] = $v[1];
      }
      if ($v[0] == 'data_access resource') {
        $dar_list[$count_access] = $v[1];
      }
      if ($v[0] == 'data_access') {
        // make sure the arrays are not empty when field is missing
        if (!isset($dat_list[$count_access])) {
          $dat_list[$count_access] = null;
        }
        if (!isset($dar_list[$count_access])) {
          $dar_list[$count_access] = null;
        }
        $count_access = $count_access + 1;
      }

      //isotopic categories
      if ($v[0] == 'iso_topic_category'){
        $itc_list[$count_itc] = $v[1];
        $count_itc = $count_itc + 1;
      }

      // dataset author title, publication year and publisher
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
      //license
      if ($v[0] == 'use_constraint identifier') {
        $license = $v[1];
      }
      if (!isset($license)) {
        $license = null;
      }
    }


    //define landing page node
 /*   $node = new stdClass();
    $node->title = $title;
    $node->type = "landing_page";
    // Sets some defaults. Invokes hook_prepare() and hook_node_prepare().
    node_object_prepare($node);
    // Or e.g. 'en' if locale is enabled.
    $node->language = LANGUAGE_NONE;
    $node->uid = $user->uid;
    // Status is 1 or 0; published or not.
    $node->status = 1;
    // Promote is 1 or 0; promoted to front page or not.
    $node->promote = 0;
    // Comment is 0, 1, 2; 0 = disabled, 1 = read only, or 2 = read/write.
    $node->comment = 0;
 */
 if(isset($title_en)) {
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
     'path' => [
       'alias' => '/datasets/' . $doi,
       'pathauto' => PathautoState::SKIP,
],
   ]);

    // Fill in the landing page node with content extracted from mmd and datacite response

    // Abstract
    //$node->field_abstract[$node->language][]['value'] = $abstract;
    if(isset($abstract_en)) {
      $node->set('field_abstract', $abstract_en);
    }
    else {
      $node->set('field_abstract', $abstract);
    }
    // Iso topic categories (can be multiple)
    for ($cn = 0; $cn < $count_itc; $cn++) {
      //$node->field_iso_topic_category[$node->language][$cn]['value'] = $itc_list[$cn];
      $node->set('field_iso_topic_category', $itc_list[$cn]);
    }

    // Citation
    if(!isset($cit_auth) || $cit_auth == '') {
      if (!isset($cit_tit) || $cit_tit  == '') {
        $cit_string = $title.', ('.date('Y', strtotime($cit_rdate)).') published by '.$cit_publ.'. ';
      }else{
        $cit_string = $cit_tit.', ('.date('Y', strtotime($cit_rdate)).') published by '.$cit_publ.'. ';
      }
    }else{
      if (!isset($cit_tit) || $cit_tit == '') {
        $cit_string = $cit_auth.', '.$title.', ('.date('Y', strtotime($cit_rdate)).') published by '.$cit_publ.'. ';
      }else{
        $cit_string = $cit_auth.', '.$cit_tit.', ('.date('Y', strtotime($cit_rdate)).') published by '.$cit_publ.'. ';
      }
    }

    //$node->field_citation[$node->language][]['value'] = $cit_string;
    $node->set('field_citation', $cit_string);

    // DOI
    //$node->field_doi[$node->language][]['url'] = $doi_uri;
    $node->set('field_doi', $doi_url);
    // License
    //if ($license == 'Public Domain') {
    //   $lic_key = 'CC0';
    //}elseif ($license == 'Attribution'){
    //   $lic_key = 'CCBY';
    //}elseif ($license == 'Share-alike'){
    //   $lic_key = 'CCBYSA';
    //}elseif ($license == 'Noncommercial'){
    //   $lic_key = 'CCBYNC';
    //}

    //$node->field_license[$node->language][0]['value'] = $license;
      $node->set('field_license', $license);


    // Contact (can be multiple)
    $hr = '';
    for ($cn = 0; $cn < $count_personnel; $cn++) {
      //$node->field_contact[$node->language][$cn] = array('value' => $hr.'<strong>Role: </strong>'.$role_list[$cn].'
        //                                                    <strong>Name: </strong>'.$name_list[$cn].'
        //                                                    <strong>email: </strong>'.$email_list[$cn].'
        //                                                    <strong>Institution: </strong>'.$inst_list[$cn],
        //                                                  'format' => 'full_html',
        //
      $node->set('field_contact', array('value' => $hr.'<strong>Role: </strong>'.$role_list[$cn].'
                                                              <strong>Name: </strong>'.$name_list[$cn].'
                                                              <strong>email: </strong>'.$email_list[$cn].'
                                                              <strong>Institution: </strong>'.$inst_list[$cn],
                                                            'format' => 'full_html',
                                                      ));

      $hr = '<hr>';

    }

    // date start and end
    //$node->field_start_date[$node->language][]['value'] = date('Y-m-d\TH:i:s', strtotime($start_date));
    $node->set('field_start_date', date('Y-m-d\TH:i:s', strtotime($start_date)));
    if ($end_date != ''){
         $node->set('field_start_date', date('Y-m-d\TH:i:s', strtotime($end_date)));
    }else{
        $node->set('field_start_date',  null);
    }

    // bounding box
    //$node->field_north[$node->language][]['value'] = $north;
    //$node->field_south[$node->language][]['value'] = $south;
    //$node->field_east[$node->language][]['value']  = $east;
    //$node->field_west[$node->language][]['value']  = $west;

      $node->set('field_north', $north);
      $node->set('field_south', $south);
      $node->set('field_east',  $east);
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
    //$geo_value = \Drupal::service('geofield.wkt_generator')->WktBuildPolygon($points);
  /*  $geofield = array(
      'geom' => "POLYGON ((".$west." ".$north.",".$east." ".$north.",".$east." ".$south.", ".$west." ".$south.",".$west." ".$north.")) ",
      'geo_type' => 'bounds',
      'lat' => "$north",
      'lon' => "$east",
      'left' => $west,
      'top' => $north,
      'right' => $east,
      'bottom' => $south
    );
*/
    //$node->field_bnds[$node->language][0] = $geofield;
    $node->set('field_bnds', "POLYGON(".$west." ".$north.",".$east." ".$north.",".$east." ".$south.", ".$west." ".$south.",".$west." ".$north.")" );
    //$node->set('field_bnds', $geofield);
    // Access (can be multiple)
    $hr = '';
    for ($cn = 0; $cn < $count_access; $cn++) {
    /*  $node->field_access[$node->language][$cn] = array('value' => $hr.'<strong>Type: </strong>'.$dat_list[$cn].'
                                                                        <strong>Resource: </strong><a href='.$dar_list[$cn].'>'.$dar_list[$cn].'</a>',
                                                        'format' => 'full_html',
                                                      ); */
    $node->set('field_access', array('value' => $hr.'<strong>Type: </strong>'.$dat_list[$cn].'
                              <strong>Resource: </strong><a href='.$dar_list[$cn].'>'.$dar_list[$cn].'</a>',
                              'format' => 'full_html',
                            ));
      $hr = '<hr>';

    }

    //$node->path['alias'] = "datasets/".$doi;

    //$node = node_submit($node); // Prepare node for saving
    //node_save($node);
    $node->save();
  //register the url to datacite
  //curl -H "Content-Type:text/plain;charset=UTF-8" -X PUT --user username:password -d "$(printf 'doi=10.5438/JQX3-61AT\nurl=http://example.org/')" https://mds.test.datacite.org/doi/10.5438/JQX3-61AT

    $options_url = array(
                'method' => 'PUT',
                //'data' => "$(printf 'doi='.$doi.'\nurl='.$base_url.'/'.$node->path['alias'])",
                'data' => 'doi='.$doi."\nurl=".$base_url.'/'.\Drupal::service('path.alias_manager')->getAliasByPath('/datasets/' . $doi),
                'timeout' => 25,
                'headers' => array('Content-Type' => 'text/plain;charset=UTF-8',
                                   'Authorization' => 'Basic ' . base64_encode($datacite_user . (":" . $datacite_pass)),),
    );

    $options = [
     'connect_timeout' => 30,
     'debug' => false,
     'body' => 'doi='.$doi."\nurl=".$base_url.'/'.\Drupal::service('path.alias_manager')->getAliasByPath('/datasets/' . $doi),
     'headers' => array(
       'Content-Type' => 'text/plain;charset=UTF-8',
     )];
    //$client = \Drupal::httpClient();
    $result = NULL;
 /*   $url = 'https://mds.'.$datacite_url.'datacite.org/doi/';
    try {
     $client = \Drupal::httpClient();
     $request = $client->request('PUT',$url,$options);

   }
   catch (RequestException $e){
     // Log the error.
     watchdog_exception('landing_page_creator', $e);
   }*/

   //$status =  $resquest->getStatusCode();
   //$result = $request->getBody();
   // $result_reg = drupal_http_request('https://mds.'.$datacite_url.'datacite.org/doi/'.$doi, $options_url);
   $message = "Created landing page: <b>" .$title .'</b>, with node id:' . $node->id();
   $rendered_message = \Drupal\Core\Render\Markup::create($message);
   $status_message = new TranslatableMarkup ('@message', array('@message' => $rendered_message));
   \Drupal::messenger()->addMessage($status_message);
    //drupal_set_message( "Node with nid " . $node->nid . " saved!\n");

    //$form_state['redirect']  = 'node/'.$node->nid;
    $routeName = 'entity.node.canonical';
    $routeParameters = ['node' => $node->id()];
    //$url = \Drupal::url($routeName, $routeParameters);
    $path = '/node/' . $node->id();
    $alias = \Drupal::service('path.alias_manager')->getAliasByPath($path);

    $url = Url::fromUri('internal:' . $alias);
    //$url = Url::fromRoute('entity.node.canonical', [ 'node' => $node->id()]);


    $form_state->setRedirectUrl($url);

 }

 // extract mmd to the last child
 function depth_mmd($prefix, $iterator) {
   $kv_a = array();
   $prefix_ = $prefix.' ';
   if ($prefix == '') {
     $prefix_ = '';
   }
   foreach ($iterator as $k => $v) {
     if ($iterator->hasChildren()) {
       $kv_a = array_merge($kv_a, $this->depth_mmd($prefix_ . $k, $v));
       if ($k == 'personnel' || $k == 'data_access') {
         $kv_a[] = array($k);
       }
     } else {
       //add mmd keys and values to form_state
       $kv_a[] = array($prefix_ . $k, (string)$v);
     }
   }
   return $kv_a; //this function returns an array of arrys
}

function sxiToXpath($sxi, $key = null, &$tmp = null)
{
    $keys_arr = array();
    //get the keys count array
    for ($sxi->rewind(); $sxi->valid(); $sxi->next())
    {
        $sk = $sxi->key();
        if (array_key_exists($sk, $keys_arr))
        {
            $keys_arr[$sk]+=1;
            $keys_arr[$sk] = $keys_arr[$sk];
        }
        else
        {
            $keys_arr[$sk] = 1;
        }
    }
    //create the xpath
    for ($sxi->rewind(); $sxi->valid(); $sxi->next())
    {
        $sk = $sxi->key();
        if (!isset($$sk))
        {
            $$sk = 1;
        }
        if ($keys_arr[$sk] >= 1)
        {
            $spk = $sk . '[' . $$sk . ']';
            $keys_arr[$sk] = $keys_arr[$sk] - 1;
            $$sk++;
        }
        else
        {
            $spk = $sk;
        }
        $kp = $key ? $key . '/' . $spk : '/' . $sxi->getName() . '/' . $spk;
        if ($sxi->hasChildren())
        {
            $this->sxiToXpath($sxi->getChildren(), $kp, $tmp);
        }
        else
        {
            $tmp[$kp] = strval($sxi->current());
        }
        $at = $sxi->current()->attributes();
        if ($at)
        {
            $tmp_kp = $kp;
            foreach ($at as $k => $v)
            {
                $kp .= '/@' . $k;
                $tmp[$kp] = $v;
                $kp = $tmp_kp;
            }
        }
    }
    return $tmp;
}

function xmlToXpath($xml)
{
    $sxi = new \SimpleXmlIterator($xml);
    return $this->sxiToXpath($sxi);
}

}
