<?php

namespace Drupal\dept_etgrm\Commands;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dept_etgrm\EtgrmBatchService;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for interacting with ETGRM.
 */
class EtgrmCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * MyModuleCommands constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct();
    $this->configFactory = $config_factory;
  }

  /**
   * Remove all relationships.
   *
   * @command etgrm:removeAll
   * @aliases etgrm:ra
   */
  public function removeAllCommand() {

    $ts = $this->configFactory->get('dept_etgrm.data')->get('processed_ts');

    if (!empty($ts) || $ts > 0) {
      $dbConn = Database::getConnection('default', 'default');

      $this->io()->title("Removing imported Group Content entities");

      $this->io()->write("Removing rows from group_content table");
      $dbConn->query("DELETE gc FROM {group_content} AS gc INNER JOIN {group_content_field_data} AS gfd ON gc.id = gfd.id WHERE gfd.created = :ts", [
        ':ts' => $ts,
      ]);
      $this->io()->writeln(" ✅");

      $this->io()->write("Removing rows from group_content_field_data table");
      $dbConn->query("DELETE gfd FROM {group_content_field_data} as gfd WHERE gfd.created = :ts", [
        ':ts' => $ts,
      ]);
      $this->io()->writeln(" ✅");

      $this->io()->success('Finished');
    }


  }



  /**
   * Create all relationships.
   *
   * @command etgrm:createAll
   * @aliases etgrm:ca
   */
  public function all() {
    $schema = Database::getConnectionInfo('default')['default']['database'];
    $dbConn = Database::getConnection('default', 'default');
    $conf = $this->configFactory->getEditable('dept_etgrm.data');
    
    $ts = time();

    $this->io()->title("Creating group content for migrated nodes.");

    $this->io()->write("Building node to group relationships");
    $dbConn->query("call CREATE_GROUP_RELATIONSHIPS('$schema')")->execute();
    $this->io()->writeln(" ✅");

    $this->io()->write("Expanding zero based domains to all groups");
    $dbConn->query("call PROCESS_GROUP_ZERO_RELATIONSHIPS()")->execute();
    $this->io()->writeln(" ✅");

    $this->io()->write("Creating Group Content data (this may take a while)");
    $dbConn->query("call PROCESS_GROUP_RELATIONSHIPS($ts)")->execute();
    $this->io()->writeln(" ✅");

    $conf->set('processed_ts', $ts);
    $conf->save();

    $this->io()->success("Finished");
  }

}
