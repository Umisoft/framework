<?php

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use umi\orm\metadata\ICollectionDataSource;

return function (ICollectionDataSource $dataSource) {

    $masterServer = $dataSource->getMasterServer();
    $schemaManager = $masterServer
        ->getConnection()
        ->getSchemaManager();
    $tableScheme = new Table($dataSource->getSourceName());

    $tableScheme->addOption('engine', 'InnoDB');

    $tableScheme
        ->addColumn('id', Type::INTEGER)
        ->setAutoincrement(true);
    $tableScheme
        ->addColumn('guid', Type::STRING)
        ->setNotnull(false);
    $tableScheme
        ->addColumn('type', Type::TEXT)
        ->setNotnull(false);
    $tableScheme
        ->addColumn(
            'version',
            Type::INTEGER
        )
        ->setUnsigned(true)
        ->setDefault(1);

    $tableScheme
        ->addColumn('pid', Type::INTEGER)
        ->setNotnull(false);
    $tableScheme
        ->addColumn('mpath', Type::TEXT)
        ->setNotnull(false);
    $tableScheme
        ->addColumn('uri', Type::TEXT)
        ->setNotnull(false);
    $tableScheme
        ->addColumn('slug', Type::STRING)
        ->setNotnull(false);
    $tableScheme
        ->addColumn('level', Type::INTEGER)
        ->setUnsigned(true)
        ->setNotnull(false);
    $tableScheme
        ->addColumn('order', Type::INTEGER)
        ->setUnsigned(false)
        ->setNotnull(false);
    $tableScheme
        ->addColumn('child_count', Type::INTEGER)
        ->setUnsigned(true)
        ->setDefault(0);

    $tableScheme
        ->addColumn('publish_time', Type::DATE)
        ->setNotnull(false);
    $tableScheme
        ->addColumn('title', Type::STRING)
        ->setNotnull(false);
    $tableScheme
        ->addColumn('title_en', Type::STRING)
        ->setNotnull(false);
    $tableScheme
        ->addColumn('content', Type::TEXT)
        ->setNotnull(false);

    $tableScheme->setPrimaryKey(['id']);
    $tableScheme->addUniqueIndex(['guid'], 'post_guid');
    $tableScheme->addIndex(['pid'], 'post_parent');
    $tableScheme
        ->addUniqueIndex(['pid', 'slug'], 'post_pid_slug');
    //    $tableScheme->addUniqueIndex(['mpath'], 'hierarchy_mpath', [], ['mpath' => ['size' => 64]]);
    //    $tableScheme->addIndex(['uri'], 'hierarchy_uri', [], ['uri' => ['size' => 64]]);
    //    $tableScheme->addIndex(['type'], 'hierarchy_type', [], ['type' => ['size' => 64]]);


    $ftHierarchy = $schemaManager->listTableDetails('umi_mock_hierarchy');

    $tableScheme->addForeignKeyConstraint(
        $ftHierarchy,
        ['pid'],
        ['id'],
        ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE'],
        'FK_post_parent'
    );
    $schemaManager->createTable($tableScheme);

};
