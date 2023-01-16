<?php

namespace Drupal\dept_core;

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
   * Constructs a DepartmentManager object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The Domain negotiator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    DomainNegotiatorInterface $domain_negotiator,
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger) {
    $this->domainNegotiator = $domain_negotiator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns the Department for the current domain.
   */
  public function getCurrentDepartment() {
    $active_domain = $this->domainNegotiator->getActiveDomain();

    return $this->getDepartment($active_domain->id());
  }

  /**
   * Returns all Departments as an array of objects.
   */
  public function getAllDepartments() {
    return $this->entityTypeManager->getStorage('department')->loadMultiple();
  }

  /**
   * Returns a department.
   *
   * @param string $id
   *   The department ID to load.
   */
  public function getDepartment(string $id) {
    // Ignore the site administration domain.
    if ($id === 'dept_admin') {
      return NULL;
    }

    return $this->entityTypeManager->getStorage('department')->load($id);
  }

}
