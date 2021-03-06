<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace umi\hmvc\component\response;

/**
 * Интерфейс для внедрения возможности создания результата работы компонента.
 */
interface IComponentResponseAware
{
    /**
     * Устанавливает фабрику для создания результатов работы компонента.
     * @param IComponentResponseFactory $factory фабрика
     */
    public function setComponentResponseFactory(IComponentResponseFactory $factory);
}
 