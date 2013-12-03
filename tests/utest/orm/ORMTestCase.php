<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */
namespace utest\orm;

use utest\dbal\TDbalSupport;
use utest\TestCase;

/**
 * Тест кейс для ORM
 */
abstract class ORMTestCase extends TestCase
{
    use TORMSupport;
    use TDbalSupport;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registerORMTools();
        $this->registerDbalTools();
        parent::setUp();
    }
}
