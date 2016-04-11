<?php

/**
 * @file
 * Mentor API search and results.
 */

/**
 * Implements menu().
 */
function mentor_menu() {
  $items['admin/config/services/mentor'] = array (
    'title' => 'Mentor API',
    'description' => 'Update the Mentor API Key and URL.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mentor_api_settings'),
    'access arguments' => array('administer site configuration'),
  );

  return $items;
}

/**
 * Form builder; Configure the mentor API.
 */
function mentor_api_settings() {

  $forms['mentor_api_url'] = array (
    '#type' => 'textfield',
    '#title' => t('API URL'),
    '#default_value' => variable_get('mentor_api_url', ''),
    '#description' => t('Enter the URL use for Mentoring API.'),
  );

  $forms['mentor_api_key'] = array (
    '#type' => 'textfield',
    '#title' => t('Mentor API Key'),
    '#default_value' => variable_get('mentor_api_key', ''),
    '#description' => t('Enter the API Key provided by Mentor team.'),
  );

  $forms['mentor_tos'] = array (
    '#type' => 'textarea',
    '#title' => t('Term of Search'),
    '#default_value' => variable_get('mentor_tos', ''),
  );

   return system_settings_form($forms);
}

/**
 * Implements hook_theme().
 */
function mentor_theme() {
  return array(
    'mentor_item' => array(
      'variables' => array('item' => NULL),
      'template' => 'mentor-item',
    ),
  );
}

/**
 * Implements hook_block_info().
 */
function mentor_block_info() {

  $blocks['find-mentor'] = array(
    'info' => t('Find Mentor'),
    'cache' => DRUPAL_NO_CACHE
  );

  $blocks['mentor-results'] = array(
    'info' => t('Mentor Oppotunities'),
    'cache' => DRUPAL_NO_CACHE
  );

  return $blocks;
}

/**
 * Implements hook_block_view().
 *
 * Displays search and result block.
 */
function mentor_block_view($delta = '') {
  $block = array();

  if ($delta == 'find-mentor') {
    $block['subject'] = t('Find A Mentor');
    $block['content'] = drupal_get_form('mentor_block_form');
  }

  if ($delta == 'mentor-results') {

    $block['subject'] = t('Search For Your Mentor Oppotunity');

    $block['content']['#markup'] = '';
    if (isset($_SESSION['mentor']['zip_code'])) {
      $block['content']['#markup'] = _mentor_results_block_content();
    }
    else {
      $block['content']['#markup'] = t('<p class="no-results">Please conduct a search for mentoring opportunities to view search results here.</p>');
    }

    $block['content']['#attached'] = array(
      'css' => array(drupal_get_path('module', 'mentor') . '/mentor.css'),
      'js' => array(drupal_get_path('module', 'mentor') . '/mentor.js'),
      'group' => CSS_DEFAULT,
      'every_page' => FALSE,
      'preprocess' => TRUE,
    );
  }

  return $block;
}

/**
 * Fetch data and render with custom item template.
 *
 * @return string
 *   The rendered list of items for the block content.
 */
function _mentor_results_block_content() {
  $output = '';
  $zip_code = $_SESSION['mentor']['zip_code'];
  $distance = $_SESSION['mentor']['distance'];
  $first_name = $_SESSION['mentor']['first_name'];
  $last_name = $_SESSION['mentor']['last_name'];
  $email = $_SESSION['mentor']['email'];
  
  // Get data.
  $jobs = mentor_get_data($zip_code, $distance, $first_name, $last_name, $email);

  if (empty($jobs->records)) {
    $output = t('<p class="no-results">No available opportunity within this distance, please update your search criteria to wider area.</p>');
  }
  else {
    foreach ($jobs->records as $job) {
      $output .= theme('mentor_item', array('item' => $job));
    }
  }

  $output = '<ul class="mentor" id="mentor">' . $output . '</ul>';

  return $output;
}

/**
 * Implements hook_forms().
 */
function mentor_forms() {

  $forms['mentor_block_form']= array(
    'callback' => 'mentor_search_box',
    'callback arguments' => array('mentor_block_form'),
  );

  return $forms;
}

/**
 * Output a search form for looking mentor position.
 */
function mentor_search_box($form, &$form_state, $form_id) {
  $form['zip_code'] = array(
    '#type' => 'textfield',
    '#title' => t('Zip Code'),
    '#size' => 15,
    '#required' => TRUE,
    '#default_value' => isset($_SESSION['mentor']['zip_code']) ? $_SESSION['mentor']['zip_code'] : '',
    '#attributes' => array(
      'title' => t('Enter the zip code wish to search for.'),
      'placeholder' => t('Zip Code'),
    )
  );

  $form['distance'] = array(
    '#type' => 'select',
    '#title' => t('Distance'),
    '#options' => array(
      5 => t('5'),
      10 => t('10'),
      15 => t('15'),
      25 => t('25')
    ),
    '#default_value' => isset($_SESSION['mentor']['distance']) ? $_SESSION['mentor']['distance'] : 15,
    '#required' => TRUE,
    '#description' => t('Choose Desired Search Distance:'),
  );

  $form['first_name'] = array(
    '#type' => 'textfield',
    '#title' => t('First Name'),
    '#size' => 25,
    '#default_value' => isset($_SESSION['mentor']['first_name']) ? $_SESSION['mentor']['first_name'] : '',
    '#required' => TRUE,
    '#attributes' => array('placeholder' => t('First Name')),
  );

  $form['last_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Last Name'),
    '#size' => 25,
    '#default_value' => isset($_SESSION['mentor']['last_name']) ? $_SESSION['mentor']['last_name'] : '',
    '#required' => TRUE,
    '#attributes' => array('placeholder' => t('Last Name')),
  );

  $form['email'] = array(
    '#type' => 'textfield',
    '#title' => t('Email'),
    '#size' => 25,
    '#default_value' => isset($_SESSION['mentor']['email']) ? $_SESSION['mentor']['email'] : '',
    '#required' => TRUE,
    '#attributes' => array('placeholder' => t('Email')),
  );

  $form['tos'] = array(
    '#markup' => variable_get('mentor_tos', t('*By searching the database and clicking the checkbox below, you agree to share your information with MENTOR: The National Mentoring Partnership, its affiliates, and any program you choose to contact and will be used to provide additional mentoring opportunities in your area and around the country. CNCS uses this website (Serve.gov) to promote mentoring opportunities stored in MENTOR\'s Mentoring Connector database. CNCS does not own, maintain, or manage any opportunities themselves. Your information will not be kept by CNCS or the White House.')),
    '#prefix' => '<div class="mentor-tos">',
    '#suffix' => '</div>', 

  );

  $form['agree_tos'] = array(
    '#type' => 'checkbox',
    '#title' => t('I agree with the terms of this search.'),
    '#default_value' => isset($_SESSION['mentor']['agree_tos']) ? $_SESSION['mentor']['agree_tos'] : FALSE,
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'mentor_commands_replace_callback',
    ),
  );

  $form['actions1'] = array(
    '#type' => 'actions',
    '#prefix' => '<div id="mentor-search">',
    '#suffix' => '</div>',
  );
  $form['actions1']['submit'] = array (
    '#type' => 'submit', 
    '#value' => t('Search'),
  );
  $form['#submit'][] = 'mentor_search_box_submit';

  return $form;
}

/**
 * Process a block search form submission.
 */
function mentor_search_box_submit($form, &$form_state) {

  $_SESSION['mentor']['zip_code'] = check_plain($form_state['values']['zip_code']);
  $_SESSION['mentor']['distance'] = check_plain($form_state['values']['distance']);
  $_SESSION['mentor']['first_name'] = check_plain($form_state['values']['first_name']);
  $_SESSION['mentor']['last_name'] = check_plain($form_state['values']['last_name']);
  $_SESSION['mentor']['email'] = check_plain($form_state['values']['email']);
  $_SESSION['mentor']['agree_tos'] = $form_state['values']['agree_tos'];

}

/**
 * Form validation handler for mentor_search_box_submit().
 */
function mentor_search_box_validate($form, &$form_state) {

  if (!empty($form_state['values']['zip_code'])) {
    if (!preg_match("/^([0-9]{5})(-[0-9]{4})?$/i", $form_state['values']['zip_code'])) {
      form_set_error('invalid_zip',t('Invalid zip code.'));
    }
  }

  $first_name = $form_state['values']['first_name'];
  if (!empty($first_name)) {

    if (substr($first_name, 0, 1) == ' ') {
      form_set_error('invalid_firstname', t ('The First Name cannot begin with a space.'));
    }
    if (substr($first_name, -1) == ' ') {
      form_set_error('invalid_firstname', t ('The First Name cannot end with a space.'));
    }
    if (strpos($first_name, '  ') !== FALSE) {
      form_set_error('invalid_firstname', t ('The First Name cannot contain double spaces or more in a row.'));
    }
    if (preg_match('/[^\x{80}-\x{F7} a-z0-9@_.\'-]/i', $first_name)) {
       form_set_error('invalid_firstname', t ('The First Name contains an illegal character.'));
    }
    if (preg_match('/[\x{80}-\x{A0}' . // Non-printable ISO-8859-1 + NBSP
    '\x{AD}' . // Soft-hyphen
    '\x{2000}-\x{200F}' . // Various space characters
    '\x{2028}-\x{202F}' . // Bidirectional text overrides
    '\x{205F}-\x{206F}' . // Various text hinting characters
    '\x{FEFF}' . // Byte order mark
    '\x{FF01}-\x{FF60}' . // Full-width latin
    '\x{FFF9}-\x{FFFD}' . // Replacement characters
    '\x{0}-\x{1F}]/u', // NULL byte and control characters
  $first_name)) {
      form_set_error('invalid_firstname', t ('The First Name contains an illegal character.'));
    }
    if (drupal_strlen($first_name) > USERNAME_MAX_LENGTH) {
      form_set_error('invalid_firstname', t('The First Name %first_name is too long: it must be %max characters or less.', array('%first_name' => $first_name, '%max' => USERNAME_MAX_LENGTH)));
    }
  }

  $last_name = $form_state['values']['last_name'];
  if (!empty($form_state['values']['last_name'])) {
    if (substr($last_name, 0, 1) == ' ') {
      form_set_error('invalid_lastname', t ('The Last Name cannot begin with a space.'));
    }
    if (substr($last_name, -1) == ' ') {
      form_set_error('invalid_lastname', t ('The Last Name cannot end with a space.'));
    }
    if (strpos($last_name, '  ') !== FALSE) {
      form_set_error('invalid_lastname', t ('The Last Name cannot contain multiple spaces in a row.'));
    }
    if (preg_match('/[^\x{80}-\x{F7} a-z0-9@_.\'-]/i', $last_name)) {
       form_set_error('invalid_lastname', t ('The Last Name contains an illegal character.'));
    }
    if (preg_match('/[\x{80}-\x{A0}' . // Non-printable ISO-8859-1 + NBSP
    '\x{AD}' . // Soft-hyphen
    '\x{2000}-\x{200F}' . // Various space characters
    '\x{2028}-\x{202F}' . // Bidirectional text overrides
    '\x{205F}-\x{206F}' . // Various text hinting characters
    '\x{FEFF}' . // Byte order mark
    '\x{FF01}-\x{FF60}' . // Full-width latin
    '\x{FFF9}-\x{FFFD}' . // Replacement characters
    '\x{0}-\x{1F}]/u', // NULL byte and control characters
  $last_name)) {
      form_set_error('invalid_lastname', t ('The Last Name contains an illegal character.'));
    }
    if (drupal_strlen($last_name) > USERNAME_MAX_LENGTH) {
      form_set_error('invalid_lastname', t('The Last Name %last_name is too long: it must be %max characters or less.', array('%last_name' => $last_name, '%max' => USERNAME_MAX_LENGTH)));
    }
  }

  if (!empty($form_state['values']['email']) && !valid_email_address($form_state['values']['email'])) {
    form_set_error('invalid_email',t('Invalid email address.'));
  }

}

/**
 * Callback for 'replace'.
 *
 * @see ajax_command_replace()
 */
function mentor_commands_replace_callback($form, $form_state) {
  $commands = array();

  if ($form_state['values']['agree_tos']) {
    $commands[] = ajax_command_replace('#edit-actions1', '<div class="form-actions form-wrapper" id="edit-actions1"><input type="submit" id="edit-submit" name="op" value="Search" class="form-submit"></div>');

  }
  else {
    $commands[] = ajax_command_replace('#edit-actions1', '<div class="form-actions form-wrapper" id="edit-actions1"><input disabled="disabled" type="submit" id="edit-submit" name="op" value="Search" class="form-submit form-button-disabled"></div>');
  }

  return array('#type' => 'ajax', '#commands' => $commands);
}

/**
 * Processes variables for mentor-item.tpl.php.
 *
 * @see mentor-item.tpl.php
 */
function template_preprocess_mentor_item(&$variables) {
  $item = $variables['item'];

  $variables['mentor_program_name'] = check_plain($item->programName);
  $variables['mentor_link'] = check_url($item->link);
  $variables['mentor_ages_served'] = $item->ageOfYouthServed;
  $variables['mentor_youth_served'] = $item->youthServed;
  $variables['mentor_primary_meeting_location'] = $item->primaryMeetingLocation;
  $variables['mentor_zip_code'] = $item->physicalZipCode;
  $variables['mentor_mentoringProgramDescription'] = $item->mentoringProgramDescription;
  $variables['mentor_mentorDescription'] = $item->mentorDescription;
  $variables['mentor_minimumMatchCommitmentInMonths'] = $item->minimumMatchCommitmentInMonths;

}

/**
 * Retrieve data from Civicore API.
 *
 * This API returns search result for mentoring oppotunity.
 *
 * @param string $zip_code
 *   
 */
function mentor_get_data($zip_code, $distance, $first_name, $last_name, $email) {

  // Connection string.
  $url = format_string('@api_url&apiKey=@api_key&email=@email&firstName=@firstName&lastName=@lastName&zipCode=@zipCode&distance=@distance',
    array('@api_url' => variable_get('mentor_api_url'),
          '@api_key' => variable_get('mentor_api_key'),
          '@zipCode' => $zip_code,
          '@distance' => $distance,
          '@firstName' => $first_name,
          '@lastName' => $last_name,
          '@email' => $email
    ));

  // Request data.
  $result = drupal_http_request($url);

  switch ($result->code) {
  case 200:
  case 301:
  case 302:
  case 304:
  case 307:
    return json_decode($result->data);

  default:
    drupal_set_message(t('Connection error "%error".', array('%error' => $result->code . ' ' . $result->error)), 'error');
  }

}
?>

