<?php
/**
 * Glossary plugin for Craft CMS 3.x
 *
 * Create a glossary of terms
 *
 * @link      https://percipio.london
 * @copyright Copyright (c) 2022 percipiolondon
 */

namespace percipiolondon\glossary\migrations;

use percipiolondon\glossary\Glossary;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\helpers\Db;

/**
 * Glossary Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    percipiolondon
 * @package   Glossary
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;

        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->dropForeignKeys();
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

    // glossary table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%glossary}}');
        if ($tableSchema === null) {

            $tablesCreated = true;

            $this->createTable(
                '{{%glossary}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    // fk
                    'siteId' => $this->integer()->notNull(),
                    'parentId' => $this->integer(),
                    // Custom columns in the table
                    'term' => $this->string(255)->notNull(),
                ]
            );

            $this->createTable(
                '{{%glossary_definition}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    // fk
                    'glossaryId' => $this->integer(),
                    // Custom columns in the table
                    'sectionHandle' => $this->string(255),
                    'definition' => $this->longText()
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes(): void
    {
        // glossary table
        $this->createIndex(null,'{{%glossary}}', 'siteId', false);
        $this->createIndex(null,'{{%glossary}}', 'term', true);
        $this->createIndex(null,'{{%glossary}}', 'parentId', false);
        $this->createIndex(null,'{{%glossary_definition}}', 'sectionHandle', false);
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        // glossary table
        $this->addForeignKey(null, '{{%glossary}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%glossary}}', ['parentId'], '{{%glossary}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%glossary_definition}}', ['sectionHandle'], '{{%sections}}', ['handle'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%glossary_definition}}', ['glossaryId'], '{{%glossary}}', ['id'], 'CASCADE', 'CASCADE');
    }

    /**
     *
     */
    public function dropForeignKeys(): void
    {
        $tables = [
            'glossary_definition',
            'glossary'
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists('{{%' . $table . '}}')) {
                Db::dropAllForeignKeysToTable('{{%' . $table . '}}');
            }
        }
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
    // glossary table
        $this->dropTableIfExists('{{%glossary_definition}}');
        $this->dropTableIfExists('{{%glossary}}');
    }
}
