<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace utest\orm\func\collection\simple;

use utest\orm\ORMDbTestCase;

/**
 * Тесты запросов коммита объектов простой коллекции
 */
class SimpleCollectionPersistQueriesTest extends ORMDbTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getCollections()
    {
        return [
            self::SYSTEM_HIERARCHY,
            self::GUIDES_CITY,
            self::GUIDES_COUNTRY,
            self::USERS_GROUP,
            self::USERS_USER,
            self::USERS_PROFILE,
        ];
    }

    public function testAddModifyDeleteObjectQueries()
    {

        $userCollection = $this->collectionManager->getCollection(self::USERS_USER);

        $user = $userCollection->add();
        $user->setValue('login', 'test_login');

        $this->objectPersister->commit();
        $queries = [
            [
                'umi\dbal\builder\InsertBuilder',
                [
                    ':type'  => ['users_user.base', \PDO::PARAM_STR],
                    ':guid'  => [$user->getGUID(), \PDO::PARAM_STR],
                    ':login' => ['test_login', \PDO::PARAM_STR]
                ]
            ]
        ];

        $this->assertEquals($queries, $this->getQueries(), 'После добавления объекта ожидается один INSERT');

        $this->setQueries([]);
        $this->objectPersister->commit();
        $this->assertEquals(
            [],
            $this->getQueries(),
            'Ожидается, что после повторного коммита без изменений не будет произведено ни одного запроса'
        );

        $this->setQueries([]);
        $user->setValue('login', 'new_test_login');
        $this->objectPersister->commit();

        $queries = [
            [
                'umi\dbal\builder\UpdateBuilder',
                [
                    ':login'      => ['new_test_login', \PDO::PARAM_STR],
                    ':objectId'   => [1, \PDO::PARAM_INT],
                    ':newversion' => '`version` + (1)',
                    ':version'    => [1, \PDO::PARAM_INT]
                ]
            ]
        ];

        $this->assertEquals($queries, $this->getQueries(), 'После изменения объекта ожидается один UPDATE-запрос');

        $this->setQueries([]);
        $this->objectPersister->commit();
        $this->assertEquals(
            [],
            $this->getQueries(),
            'Ожидается, что после повторного коммита без изменений не будет произведено ни одного запроса'
        );

        $this->setQueries([]);
        $this->objectPersister->markAsDeleted($user);
        $this->objectPersister->commit();
        $queries = [
            [
                'umi\dbal\builder\DeleteBuilder',
                [
                    ':objectId' => [1, \PDO::PARAM_INT]
                ]
            ]
        ];
        $this->assertEquals($queries, $this->getQueries(), 'После удаления объекта ожидается один DELETE-запрос');

        $this->setQueries([]);
        $this->objectPersister->commit();
        $this->assertEquals(
            [],
            $this->getQueries(),
            'Ожидается, что после повторного коммита без изменений не будет произведено ни одного запроса'
        );

    }

    public function testBelongsToRelationQueries()
    {

        $groupCollection = $this->collectionManager->getCollection(self::USERS_GROUP);

        $group = $groupCollection->add();
        $group->setValue('name', 'test_group1');

        $userCollection = $this->collectionManager->getCollection(self::USERS_USER);

        $user = $userCollection->add();
        $user->setValue('login', 'test_login');
        $user->setValue('group', $group);

        $this->objectPersister->commit();

        //todo! improve assertion
        $queries = [
            [
                'umi\dbal\builder\InsertBuilder',
                [
                    ':type' => ['users_group.base', \PDO::PARAM_STR],
                    ':guid' => [$group->getGUID(), \PDO::PARAM_STR],
                    ':name' => ['test_group1', \PDO::PARAM_STR]
                ]
            ],
            [
                'umi\dbal\builder\InsertBuilder',
                [
                    ':type'     => ['users_user.base', \PDO::PARAM_STR],
                    ':guid'     => [$user->getGUID(), \PDO::PARAM_STR],
                    ':login'    => ['test_login', \PDO::PARAM_STR],
                    ':group_id' => [null, \PDO::PARAM_NULL]
                ]
            ],
            [
                'umi\dbal\builder\UpdateBuilder',
                [
                    ':group_id'   => [1, \PDO::PARAM_INT],
                    ':newversion' => '`version` + (1)',
                    ':objectId'   => [1, \PDO::PARAM_INT],
                    ':version'    => [1, \PDO::PARAM_INT]
                ]
            ]
        ];

        $insertsExpected = 2;
        $updatesExpected = 1;

        $insertsActual = count($this->getOnlyQueries('insert'));
        $updatesActual = count($this->getOnlyQueries('update'));
        $queriesActual = count($this->getQueries()) /* -2 for commit*/;
        $this->assertTrue(
            (
                $insertsExpected == $insertsActual
                && ($updatesExpected == $updatesActual)
                && $queriesActual == ($insertsExpected + $updatesExpected)
            ),
            'После добавления двух связанных объектов ожидаются два INSERT-запроса и 1 UPDATE-запрос'
        );

        $this->assertEquals(2, $user->getVersion(), 'Ожидается, что у пользователя версия 2');

        $this->setQueries([]);

        $group2 = $groupCollection->add();
        $group2->setValue('name', 'test_group2');
        $user->setValue('group', $group2);

        $this->objectPersister->commit();

        $queries = [
            [
                'umi\dbal\builder\InsertBuilder',
                [
                    ':name' => ['test_group2', \PDO::PARAM_STR],
                    ':type' => ['users_group.base', \PDO::PARAM_STR],
                    ':guid' => [$group2->getGUID(), \PDO::PARAM_STR]
                ]
            ],
            [
                'umi\dbal\builder\UpdateBuilder',
                [
                    ':group_id'   => [2, \PDO::PARAM_INT],
                    ':objectId'   => [1, \PDO::PARAM_INT],
                    ':newversion' => '`version` + (1)',
                    ':version'    => [2, \PDO::PARAM_INT]
                ]
            ]
        ];

        $this->assertEquals(
            $queries,
            $this->getQueries(),
            'После добавления объекта и выставления его в качестве значения ожидается один INSERT-запрос и один UPDATE-запрос'
        );
        $this->assertEquals(3, $user->getVersion(), 'Ожидается, что у пользователя версия 3');

        $this->setQueries([]);
        $user->setValue('group', $group);
        $this->objectPersister->commit();

        $queries = [
            [
                'umi\dbal\builder\UpdateBuilder',
                [
                    ':group_id'   => [1, \PDO::PARAM_INT],
                    ':objectId'   => [1, \PDO::PARAM_INT],
                    ':newversion' => '`version` + (1)',
                    ':version'    => [3, \PDO::PARAM_INT]
                ]
            ]
        ];

        $this->assertEquals(
            $queries,
            $this->getQueries(),
            'После установления связи между 2-мя существующими запросами ожидается один UPDATE-запрос'
        );
        $this->assertEquals(4, $user->getVersion(), 'Ожидается, что у пользователя версия 4');

        $this->setQueries([]);
        $user->unload();
        $group->unload();

        $userCollection = $this->collectionManager->getCollection(self::USERS_USER);
        $user = $userCollection->getById(1);
        $user->setValue('group', $group2);
        $this->objectPersister->commit();

        $queries = [
            [
                'umi\dbal\builder\SelectBuilder',
                [
                    ':value0' => [1, \PDO::PARAM_INT]
                ]
            ],
            [
                'umi\dbal\builder\SelectBuilder',
                [
                    ':value0' => [1, \PDO::PARAM_INT]
                ]
            ],
            [
                'umi\dbal\builder\UpdateBuilder',
                [
                    ':group_id'   => [2, \PDO::PARAM_INT],
                    ':objectId'   => [1, \PDO::PARAM_INT],
                    ':newversion' => '`version` + (1)',
                    ':version'    => [4, \PDO::PARAM_INT]
                ]
            ]
        ];
        $this->assertEquals(
            $queries,
            $this->getQueries(),
            'Ожидается два SELECT-запроса и один UPDATE запрос при выставления значения незагруженному объекту с существующим значением'
        );
        $this->assertEquals(5, $user->getVersion(), 'Ожидается, что у пользователя версия 5');
    }
}
