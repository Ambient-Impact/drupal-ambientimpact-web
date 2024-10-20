<?php

namespace Drupal\ambientimpact_web_components\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;

/**
 * Controller for the 'ambientimpact_web_components.component_item_demo' route.
 */
class ComponentItemDemoController extends ControllerBase {
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
   * Builds and returns the Component item demo render array.
   *
   * @param string $componentMachineName
   *   The machine name of the Component to display the demo of.
   *
   * @return array
   *   The Component item demo render array.
   */
  public function componentItemDemo(string $componentMachineName) {
    $pluginDefinitions = $this->componentManager->getDefinitions();

    // If the Component plug-in doesn't exist, throw a 404.
    // @see https://www.drupal.org/node/1616360
    if (!isset($pluginDefinitions[$componentMachineName])) {
      throw new NotFoundHttpException();
    }

    $plugin =
      $this->componentManager->getComponentInstance($componentMachineName);

    $pluginDefinition = $pluginDefinitions[$componentMachineName];

    // If the Component plug-in doesn't have a demo, throw a 404.
    // @see https://www.drupal.org/node/1616360
    if (!$plugin->hasDemo()) {
      throw new NotFoundHttpException();
    }

    $renderArray = [
      '#theme'  => 'ambientimpact_component_demo',
      '#attached' => ['library' => [
        'ambientimpact_web_components/component.component_demo',
      ]],
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

    $componentRenderArray = $plugin->getDemo();

    // If the Component's render array has a '#demo' key, merge it into our
    // render array.
    if (isset($componentRenderArray['#demo'])) {
      $renderArray += $componentRenderArray;

    // If it doesn't have a '#demo' key, assume it contains some children and
    // set it to the '#demo' key on our render array.
    } else {
      $renderArray['#demo'] = $componentRenderArray;
    }

    return $renderArray;
  }

  /**
   * Component item demo route title callback.
   *
   * @param string $componentMachineName
   *   The machine name of the Component to display the demo of.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The Component item demo route title.
   */
  public function componentItemDemoTitle(string $componentMachineName) {
    $pluginDefinitions = $this->componentManager->getDefinitions();

    return $this->t(
      '@componentName component demo',
      [
        '@componentName'  => $pluginDefinitions[$componentMachineName]['title'],
      ]
    );
  }
}
