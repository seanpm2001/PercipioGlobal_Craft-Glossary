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
            $this->insertDefaultData();
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

//            $this->createTable(
//                '{{%glossary_relations}}',
//                [
//                    'id' => $this->primaryKey(),
//                    'dateCreated' => $this->dateTime()->notNull(),
//                    'dateUpdated' => $this->dateTime()->notNull(),
//                    'uid' => $this->uid(),
//                    // Custom columns in the table
//                    'glossaryId' => $this->integer(),
//                    'glossaryTermId' => $this->integer(),
//                    'glossaryDefinitionId' => $this->integer(),
//                ]
//            );
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
        $this->createIndex(
            $this->db->getIndexName('{{%glossary}}', 'term', true ),
            '{{%glossary}}',
            'term',
            true
        );
        $this->createIndex(
            $this->db->getIndexName('{{%glossary}}', 'parentId', true ),
            '{{%glossary}}',
            'parentId',
            true
        );
        $this->createIndex(
            $this->db->getIndexName('{{%glossary_definition}}', 'sectionHandle', true ),
            '{{%glossary_definition}}',
            'sectionHandle',
            true
        );
//        $this->createIndex(
//            $this->db->getIndexName('{{%glossary}}', 'id', true ),
//            '{{%glossary_relations}}',
//            'glossaryId',
//            true
//        );
//        $this->createIndex(
//            $this->db->getIndexName('{{%glossary_term}}', 'id', true ),
//            '{{%glossary_relations}}',
//            'glossaryTermId',
//            true
//        );
//        $this->createIndex(
//            $this->db->getIndexName('{{%glossary_definition}}', 'id', true ),
//            '{{%glossary_relations}}',
//            'glossaryDefinitionId',
//            true
//        );
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
    // glossary table
        $this->addForeignKey($this->db->getForeignKeyName('{{%glossary}}', 'siteId'), '{{%glossary}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%glossary}}', 'parentId'), '{{%glossary}}', 'parentId', '{{%glossary}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%glossary_definition}}', 'sectionHandle'), '{{%glossary_definition}}', 'sectionHandle', '{{%sections}}', 'handle', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%glossary_definition}}', 'glossaryId'), '{{%glossary_definition}}', 'glossaryId', '{{%glossary}}', 'id', 'CASCADE', 'CASCADE');
//        $this->addForeignKey($this->db->getForeignKeyName('{{%glossary}}', 'id'), '{{%glossary_relations}}', 'glossaryId', '{{%glossary}}', 'id', 'CASCADE', 'CASCADE');
//        $this->addForeignKey($this->db->getForeignKeyName('{{%glossary_definition}}', 'id'), '{{%glossary_relations}}', 'glossaryDefinitionId', '{{%glossary_definition}}', 'id', 'CASCADE', 'CASCADE');
//        $this->addForeignKey($this->db->getForeignKeyName('{{%glossary_term}}', 'id'), '{{%glossary_relations}}', 'glossaryTermId', '{{%glossary_term}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
    // glossary table
//        $this->dropTableIfExists('{{%glossary_relations}}');
        $this->dropTableIfExists('{{%glossary_definition}}');
        $this->dropTableIfExists('{{%glossary}}');
    }
}
