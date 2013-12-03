<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

    namespace utest\dbal\unit\builder;

    use umi\dbal\cluster\server\IServer;
    use utest\dbal\DbalTestCase;

/**
 * Тест фабрики построителей запросов
 */
class QueryResultFactoryTest extends DbalTestCase
{
    /**
     * @var IServer $sqlite
     */
    private $sqlite;
    /**
     * @var IServer $mysql
     */
    private $mysql;

    protected function setUpFixtures()
    {
        $this->sqlite = $this->getSqliteServer();
        $this->mysql = $this->getMysqlServer();
            $this->sqlite->getConnection()
                ->exec('DROP TABLE IF EXISTS `temp_test_table`');
            $this->sqlite->getConnection()
                ->exec('CREATE TABLE `temp_test_table` (`id` INTEGER PRIMARY KEY, `field1` TEXT, `field2` TEXT)');
    }

    public function testResultBuilderFactory()
    {

        $builder = $this->mysql
            ->select()
            ->from('tests_comment');
        $result = $builder->execute();
        $this->assertInstanceOf(
            'Doctrine\DBAL\Driver\ResultStatement',
            $result,
            'Ожидается, что IQueryBuilder->execute() вернет IQueryResult'
        );

        $builder = $this->sqlite
            ->select()
            ->from('temp_test_table');
        $result = $builder->execute();
        $this->assertInstanceOf(
            'Doctrine\DBAL\Driver\ResultStatement',
            $result,
            'Ожидается, что IQueryBuilder->execute() вернет IQueryResult'
        );
    }
}
