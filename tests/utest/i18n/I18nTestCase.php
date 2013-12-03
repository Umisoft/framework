<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */
namespace utest\i18n;

use utest\TestCase;

/**
 * Тест кейс локализации
 */
abstract class I18nTestCase extends TestCase
{
    use TI18nSupport;
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registerI18nTools();

        parent::setUp();
    }
}
 