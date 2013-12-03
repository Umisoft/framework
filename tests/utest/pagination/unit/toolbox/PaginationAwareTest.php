<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace utest\unit\pagination\toolbox;

use utest\AwareTestCase;
use utest\pagination\TPaginationSupport;

/**
 * Тесты внедрения зависимостей пагинации.
 */
class PaginationAwareTest extends AwareTestCase
{
    use TPaginationSupport;
    /**
     * {@inheritdoc}
     */
    protected function setUpFixtures()
    {
        $this->registerPaginationTools();
    }

    public function testPaginationAware()
    {
        $this->awareClassTest(
            'utest\pagination\mock\aware\MockPaginationAware',
            'umi\pagination\exception\RequiredDependencyException',
            'Paginator factory is not injected in class "utest\pagination\mock\aware\MockPaginationAware".'
        );

        $this->successfulInjectionTest(
            'utest\pagination\mock\aware\MockPaginationAware',
            'umi\pagination\IPaginatorFactory'
        );
    }
}