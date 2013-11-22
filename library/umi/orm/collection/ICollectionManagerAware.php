<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace umi\orm\collection;

/**
 * Интерфейс для внедрения менеджера коллекций объектов.
 */
interface ICollectionManagerAware
{
    /**
     * Устанавливает менеджер коллекций объектов
     * @param ICollectionManager $collectionManager
     */
    public function setCollectionManager(ICollectionManager $collectionManager);
}
