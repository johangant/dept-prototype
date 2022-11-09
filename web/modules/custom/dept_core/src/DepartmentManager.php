<?php

namespace Drupal\dept_core;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Service class for managing Department objects.
 */
class DepartmentManager {

  /**
   * The domain.negotiator service.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The default cache bin service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The default 'sync' config storage.
   *
   * @var \Drupal\Core\Config\FileStorage
   */
  protected $configStorageSync;

  /**
   * Constructs a DepartmentManager object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The Domain negotiator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\FileStorage $config_storage_sync
   *   The default 'sync' config storage.
   */
  public function __construct(
    DomainNegotiatorInterface $domain_negotiator,
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache,
    MessengerInterface $messenger,
    FileStorage $config_storage_sync) {
    $this->domainNegotiator = $domain_negotiator;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
    $this->messenger = $messenger;
    $this->configStorageSync = $config_storage_sync;
  }

  /**
   * Returns the Department for the current domain.
   */
  public function getCurrentDepartment() {
    $active_domain = $this->domainNegotiator->getActiveDomain();

    $dept = $this->getDepartment($active_domain->id());

    // Add a UI warning if we can't resolve the Department to the active domain.
    if ($dept === NULL) {
      $this->messenger->addWarning('Unable to resolve Department. Check Domain hostnames or Config Split settings.');
    }

    return $dept;
  }

  /**
   * Returns all Departments as an array of objects.
   */
  public function getAllDepartments() {
    $domains = $this->entityTypeManager->getStorage('domain')->loadMultiple();
    $departments = [];
    foreach ($domains as $id => $domain) {
      if (strpos($id, 'group_') === 0) {
        $departments[] = $this->getDepartment($id);
      }
    }

    return $departments;
  }

  /**
   * Returns a department.
   *
   * @param string $id
   *   The domain ID to load a department.
   */
  public function getDepartment(string $id) {
    // Return if we have a Domain that is not related to a Group.
    if (!preg_match('/group_\d+/', $id)) {
      return NULL;
    }
    $cache_item = $this->cache->get('department_' . $id);
    $department = $cache_item->data ?? '';

    if (!$department instanceof Department) {
      $department = new Department($this->entityTypeManager, $id, $this->configStorageSync);
      // Add to cache and use tags that will invalidate when the Domain or
      // Group entities change.
      $this->cache->set('department_' . $id, $department, CACHE::PERMANENT, [
        'url.site',
        'group:' . $department->groupId(),
      ]);
    }

    return $department;
  }
}
