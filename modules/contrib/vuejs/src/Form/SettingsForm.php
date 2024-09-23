<?php

namespace Drupal\vuejs\Form;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Vue.js settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * The library.discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected LibraryDiscoveryInterface $libraryDiscovery;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Constructs a form object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library.discovery service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LibraryDiscoveryInterface $library_discovery,
    ClientInterface $http_client
  ) {
    $this->libraryDiscovery = $library_discovery;
    $this->httpClient = $http_client;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('library.discovery'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'vuejs_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['vuejs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $registered_libraries = $this->libraryDiscovery->getLibrariesByExtension('vuejs');
    $libraries = $this->config('vuejs.settings')->get('libraries');

    $form['vue'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('VueJS Runtime'),
      '#tree' => TRUE,
    ];

    $form['vue']['installation'] = [
      '#type' => 'select',
      '#title' => $this->t('Installation Type'),
      '#options' => [
        'local' => $this->t('Local library'),
        'cdn' => $this->t('Use an external CDN'),
      ],
      '#default_value' => $libraries['vue']['installation'] ?? 'cdn',
      '#required' => TRUE,
    ];

    $form['vue']['development'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Development Version'),
      '#default_value' =>  $libraries['vue']['development'] ?? FALSE,
    ];

    $form['vue']['cdn'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a CDN provider'),
      '#options' => [
        'unpkg' => 'UNPKG',
        'cdnjs' => 'cdnjs',
        'jsdelivr' => 'jsDelivr',
      ],
      '#default_value' => $libraries['vue']['cdn'] ?? 'unpkg',
      '#states' => [
        'visible' => [
          'select[name="vue[installation]"]' => [ 'value' => 'cdn'],
        ],
      ],
    ];

    $form['vue']['version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Version'),
      '#size' => 9,
      '#default_value' => $libraries['vue']['version'] ?? '3.2.37',
      '#states' => [
        'invisible' => [
          'select[name="vue[installation]"]' => ['value' => 'local']
          ],
        ],
    ];

    $form['petitevue'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Petite Vue Runtime'),
      '#tree' => TRUE,
    ];

    $form['petitevue']['installation'] = [
      '#type' => 'select',
      '#title' => $this->t('Installation Type'),
      '#options' => [
        'local' => $this->t('Local library'),
        'cdn' => $this->t('Use an external CDN'),
      ],
      '#default_value' => $libraries['petitevue']['installation'] ?? 'cdn',
      '#required' => TRUE,
    ];

    $form['petitevue']['development'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Development Version'),
      '#default_value' =>  $libraries['petitevue']['development'] ?? FALSE,
    ];

    $form['petitevue']['cdn'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a CDN provider'),
      '#options' => [
        'unpkg' => 'UNPKG',
        'cdnjs' => 'cdnjs',
        'jsdelivr' => 'jsDelivr',
      ],
      '#default_value' => $libraries['petitevue']['cdn'] ?? 'unpkg',
      '#states' => [
        'visible' => [
          'select[name="petitevue[installation]"]' => [ 'value' => 'cdn'],
        ],
      ],
    ];

    $form['petitevue']['version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Version'),
      '#size' => 9,
      '#default_value' => $libraries['petitevue']['version'] ?? '0.4.1',
      '#states' => [
        'invisible' => [
          'select[name="petitevue[installation]"]' => ['value' => 'local']
          ],
        ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $libraries = $this->config('vuejs.settings')->get('libraries');

    foreach ($libraries as $library_name => $library) {
      $value = $form_state->getValue($library_name);
      $matches = [];

      if (!preg_match('/^(?<prefix>v)?(?<version>\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\.\d)?)$/', $value['version'], $matches)) {
        $form_state->setErrorByName($library_name . '][version', $this->t('Version format is not correct.'));
      }
      elseif (!empty($matches['prefix'])) {
        $form_state->setValue([$library_name, 'version'], $matches['version']);
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Check the set settings and update config as necessary.
    $vueSettings = $form_state->getValue('vue');

    $vueRuntimeFilename = $vueSettings['development'] ? 'vue.runtime.global.js' : 'vue.runtime.global.prod.js';
    $vueSettings['path'] = '/libraries/vue/dist/' . $vueRuntimeFilename;
    // Set the path for the given CDN.
    if ($vueSettings['installation'] == 'cdn') {
      switch ($vueSettings['cdn']) {
        case 'jsdelivr':
          $vueSettings['path'] = '//cdn.jsdelivr.net/npm/vue@' . $vueSettings['version'] .'/dist/' . $vueRuntimeFilename;
          break;
        case 'cdnjs':
          $vueSettings['path'] = '//cdnjs.cloudflare.com/ajax/libs/vue/' . $vueSettings['version'] .'/' . $vueRuntimeFilename;
          break;
        case 'unpkg':
        default:
          $vueSettings['path'] = '//unpkg.com/vue@' . $vueSettings['version'] .'/dist/' . $vueRuntimeFilename;
      }
    }

    // Check the set settings and update config as necessary.
    $petitieVueSettings = $form_state->getValue('petitevue');

    // @todo determine the dev filename.
    $petiteVueRuntimeFilename = $petitieVueSettings['development'] ? 'petite-vue.js' : 'petite-vue.iife.js';
    $petitieVueSettings['path'] = '/libraries/petite-vue/dist/' . $petiteVueRuntimeFilename;
    // Set the path for the given CDN.
    if ($petitieVueSettings['installation'] == 'cdn') {
      switch ($petitieVueSettings['cdn']) {
        case 'jsdelivr':
          $petitieVueSettings['path'] = '//cdn.jsdelivr.net/npm/petite-vue@' . $petitieVueSettings['version'] .'/dist/' . $petiteVueRuntimeFilename;
          break;
        case 'cdnjs':
          $petitieVueSettings['path'] = '//cdnjs.cloudflare.com/ajax/libs/petite-vue/' . $petitieVueSettings['version'] .'/' . $petiteVueRuntimeFilename;
          break;
        case 'unpkg':
        default:
          $petitieVueSettings['path'] = '//unpkg.com/petite-vue@' . $petitieVueSettings['version'] .'/dist/' . $petiteVueRuntimeFilename;
      }
    }

    $this->config('vuejs.settings')
      ->set('libraries.vue', $vueSettings)
      ->set('libraries.petitevue', $petitieVueSettings)
      ->save();
    $this->libraryDiscovery->clearCachedDefinitions();
    parent::submitForm($form, $form_state);
  }

}
