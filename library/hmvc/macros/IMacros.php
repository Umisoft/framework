<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace umi\hmvc\macros;

use umi\hmvc\dispatcher\IDispatchContext;
use umi\hmvc\view\IView;

/**
 * Интерфейс макроса.
 */
interface IMacros
{
    /**
     * Вызывает макрос.
     * @return IView|string
     */
    public function __invoke();

    /**
     * Внедряет контекст вызова макроса.
     * @param IDispatchContext $context
     * @return self
     */
    public function setContext(IDispatchContext $context);

}
 