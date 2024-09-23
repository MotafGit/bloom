<?php



/**

 * @file

 * Contains mymodule.module.

 */



use Drupal\Core\Routing\RouteMatchInterface;



/**

 * Implements hook_menu().

 */

function mymodule_menu() {

  $items = [];



  $items['vueTeste/custom-page'] = [

    'title' => 'Custom Page',

    'description' => 'A custom page for my module.',

    'page callback' => 'mymodule_custom_page',

    'access callback' => TRUE,

  ];



  return $items;

}



/**

 * Page callback for custom page.

 */

function mymodule_custom_page() {

  return [

    '#markup' => 'Hello, world!',

  ];

}

