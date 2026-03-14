<?php

namespace justinholtweb\rabbits\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->_createComponentsTable();
        $this->_createClassesTable();
        $this->_createInstancesTable();
        $this->_createTokensTable();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%rabbits_instances}}');
        $this->dropTableIfExists('{{%rabbits_tokens}}');
        $this->dropTableIfExists('{{%rabbits_classes}}');
        $this->dropTableIfExists('{{%rabbits_components}}');

        return true;
    }

    private function _createComponentsTable(): void
    {
        $this->createTable('{{%rabbits_components}}', [
            'id' => $this->integer()->notNull(),
            'handle' => $this->string(255)->notNull(),
            'componentType' => $this->string(20)->notNull()->defaultValue('atom'),
            'componentStatus' => $this->string(20)->notNull()->defaultValue('draft'),
            'tree' => $this->json(),
            'styles' => $this->json(),
            'animations' => $this->json(),
            'customCss' => $this->text(),
            'customJs' => $this->text(),
            'breakpoints' => $this->json(),
            'compiledTwig' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addPrimaryKey(null, '{{%rabbits_components}}', ['id']);
        $this->createIndex(null, '{{%rabbits_components}}', ['handle'], true);
        $this->createIndex(null, '{{%rabbits_components}}', ['componentType']);
        $this->createIndex(null, '{{%rabbits_components}}', ['componentStatus']);

        $this->addForeignKey(null, '{{%rabbits_components}}', ['id'], '{{%elements}}', ['id'], 'CASCADE');
    }

    private function _createClassesTable(): void
    {
        $this->createTable('{{%rabbits_classes}}', [
            'id' => $this->primaryKey(),
            'handle' => $this->string(255)->notNull(),
            'name' => $this->string(255)->notNull(),
            'styles' => $this->json(),
            'breakpoints' => $this->json(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%rabbits_classes}}', ['handle'], true);
    }

    private function _createInstancesTable(): void
    {
        $this->createTable('{{%rabbits_instances}}', [
            'id' => $this->primaryKey(),
            'componentId' => $this->integer()->notNull(),
            'entryId' => $this->integer(),
            'sortOrder' => $this->integer()->notNull()->defaultValue(0),
            'overrides' => $this->json(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%rabbits_instances}}', ['componentId']);
        $this->createIndex(null, '{{%rabbits_instances}}', ['entryId']);
        $this->createIndex(null, '{{%rabbits_instances}}', ['sortOrder']);

        $this->addForeignKey(null, '{{%rabbits_instances}}', ['componentId'], '{{%rabbits_components}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%rabbits_instances}}', ['entryId'], '{{%elements}}', ['id'], 'CASCADE');
    }

    private function _createTokensTable(): void
    {
        $this->createTable('{{%rabbits_tokens}}', [
            'id' => $this->primaryKey(),
            'category' => $this->string(50)->notNull(),
            'handle' => $this->string(255)->notNull(),
            'label' => $this->string(255)->notNull(),
            'value' => $this->text()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%rabbits_tokens}}', ['category']);
        $this->createIndex(null, '{{%rabbits_tokens}}', ['handle']);
        $this->createIndex(null, '{{%rabbits_tokens}}', ['category', 'handle'], true);
    }
}
