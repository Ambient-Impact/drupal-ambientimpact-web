<?php

namespace Drupal\ambientimpact_web_components\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the 'ambientimpact_web_components.component_list' route.
 */
class ComponentListController extends ControllerBase {
  /**
   * The Ambient.Impact Component plug-in manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * Controller constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component plug-in manager service.
   */
  public function __construct(ComponentPluginManagerInterface $componentManager) {
    $this->componentManager = $componentManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get(
      'plugin.manager.ambientimpact_component'
    ));
  }

  /**
   * Builds and returns the Component list render array.
   *
   * @return array
   *   The Component list render array.
   */
  public function componentList() {
    $renderArray = [
      '#theme'    => 'ambientimpact_component_list',
      '#list'     => [
        '#theme'      => 'item_list',
        '#list_type'  => 'ul',
        '#items'      => [],
        '#empty'      => $this->t('No components found.'),
      ],
      '#attached' => [
        'library'   => 'ambientimpact_web_components/component.component_list',
      ],
      // Use the same cache metadata as component discovery to rebuild this when
      // component discovery is done.
      //
      // @todo Can we instead use Drupal\Core\Cache\RefinableCacheableDependencyInterface::addCacheableDependency()?
      '#cache'  => [
        'contexts'  => $this->componentManager->getCacheContexts(),
        'max-age'   => $this->componentManager->getCacheMaxAge(),
        'tags'      => $this->componentManager->getCacheTags(),
      ],
    ];

    $pluginDefinitions = $this->componentManager->getDefinitions();

    // This array contains the plug-in titles to be sorted using a natural order
    // algorithm.
    $pluginsSorted = [];

    // Build the array of plug-in titles.
    foreach ($pluginDefinitions as $pluginID => $pluginDefinition) {
      $pluginsSorted[$pluginDefinition['id']] =
        $pluginDefinition['title']->__toString();
    }

    // Sort the array.
    // @see https://www.php.net/manual/en/function.natcasesort.php
    \natcasesort($pluginsSorted);

    foreach ($pluginsSorted as $pluginID => $pluginTitle) {
      $renderArray['#list']['#items'][$pluginID] = [
        '#theme'    => 'ambientimpact_component_list_item',
        '#pageLink' => [
          '#type'   => 'link',
          '#title'  => $pluginDefinitions[$pluginID]['title'],
          '#url'    => Url::fromRoute(
            'ambientimpact_web_components.component_item',
            ['componentMachineName' => $pluginID]
          ),
        ],
      ];

      $componentInstance = $this->componentManager
        ->getComponentInstance($pluginID);

      if (
        $componentInstance !== false &&
        $componentInstance->hasDemo() === true
      ) {
        $renderArray['#list']['#items'][$pluginID]['#demoLink'] = [
          '#type'   => 'link',
          '#title'  => $this->t('Demo'),
          '#url'    => Url::fromRoute(
            'ambientimpact_web_components.component_item_demo',
            ['componentMachineName' => $pluginID]
          ),
        ];
      }
    }

    return $renderArray;
  }
}
