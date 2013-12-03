<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace utest\authentication\unit\toolbox;

use utest\authentication\TAuthenticationSupport;
use utest\AwareTestCase;

class AuthAwareToolsTest extends AwareTestCase
{

    use TAuthenticationSupport;

    protected function setUpFixtures()
    {
        $this->registerAuthenticationTools();
    }

    public function testAware()
    {
        $this->awareClassTest(
            'utest\authentication\mock\toolbox\MockAuthentication',
            'umi\authentication\exception\RequiredDependencyException',
            'Authentication factory is not injected in class "utest\authentication\mock\toolbox\MockAuthentication".'
        );

        $this->successfulInjectionTest(
            'utest\authentication\mock\toolbox\MockAuthentication',
            'umi\authentication\IAuthenticationFactory'
        );
    }
}
