<?php

namespace Drupal\dept_rel_to_abs_urls\Plugin\Filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dept_core\DepartmentManager;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Filter(
 *   id = "rel_to_abs_url",
 *   title = @Translation("Relative to Absolute URL Filter"),
 *   description = @Translation("Transform relative URLs to absolute URLs"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class relToAbsUrlsFilter extends FilterBase implements ContainerFactoryPluginInterface  {

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Department manager.
   *
   * @var \Drupal\dept_core\DepartmentManager
   */
  protected $departmentManager;

  /**
   * Filter constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   * @param \Drupal\dept_core\DepartmentManager $department_manager
   *   The department manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DepartmentManager $department_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->departmentManager = $department_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('department.manager')
    );
  }



  // TODO: Add an admin option to restrict this by domain.

  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);


    $updated_text = preg_replace_callback(
      '/data-entity-uuid="(.+)" href="(\/\S+)"/m',
      function ($matches) {
        $node = $this->entityTypeManager->getStorage('node')->loadByProperties(['uuid' => $matches[1]]);
        $node = reset($node);
        // TODO: Check node object has getGroups method or is of a group content type plugin.
        $groups = $node->getGroups();

        if (!empty($groups)) {
          $group_id = array_key_first($groups);

          $dept = $this->departmentManager->getDepartment('group_' . $group_id);
          $hostname = $dept->hostname();

          return 'href="https://' . $hostname . $matches[2] . '"';
        }
      },
      $result
    );

    if ($updated_text) {
      $result = new FilterProcessResult($updated_text);
    }

    return $result;
  }

}
