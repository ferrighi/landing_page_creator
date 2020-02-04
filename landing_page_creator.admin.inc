<?php

// General Data Cite administration page to set the user and password of the datacite
function landing_page_creator_admin($form, &$form_state) {
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



  return system_settings_form($form);
}


