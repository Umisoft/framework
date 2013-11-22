<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace utest\orm\func\persister;

use umi\orm\object\IObject;
use utest\orm\ORMDbTestCase;

/**
 * Тесты отката коммита ObjectPersister
 */
class ObjectPersisterRollbackTest extends ORMDbTestCase
{

    protected $usedDbServerId = 'mysqlMaster';

    protected $blog1Guid;
    protected $blog2Guid;

    /**
     * {@inheritdoc}
     */
    protected function getCollections()
    {
        return [
            self::SYSTEM_HIERARCHY,
            self::USERS_GROUP,
            self::USERS_USER,
            self::BLOGS_BLOG,
            self::BLOGS_POST,
        ];
    }

    protected function setUpFixtures()
    {
        $blogsCollection = $this->collectionManager->getCollection(self::BLOGS_BLOG);

        $blog1 = $blogsCollection->add('blog1');
        $blog1->setValue('title', 'first_blog');
        $this->blog1Guid = $blog1->getGUID();

        $blog2 = $blogsCollection->add('blog2');
        $blog2->setValue('title', 'second_blog');
        $this->blog2Guid = $blog2->getGUID();

        $this->objectPersister->commit();

    }

    public function testURIConflict()
    {
        $blogsCollection = $this->collectionManager->getCollection(self::BLOGS_BLOG);
        $blogsCollection->add('blog1');

        $e = null;
        try {
            //todo! make unique index for url, slug etc
            $this->objectPersister->commit();
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается исключение, когда произошел конфликт урлов при добавлении объекта'
        );
        $parentE = $e->getPrevious();
        $this->assertInstanceOf(
            'umi\dbal\exception\RuntimeException',
            $parentE,
            'Ожидается родительское исключение БД, когда произошел конфликт урлов при добавлении объекта'
        );

    }

    public function testForeignKeyCheck()
    {

        $this->getDbCluster()
            ->modifyInternal('DELETE FROM `umi_mock_blogs` WHERE `id` = 1');
        $this->getDbCluster()
            ->modifyInternal(
            'SET foreign_key_checks = 0; INSERT INTO `umi_mock_users` set `id` = "10", `type` = "users_user.base", `group_id` = "10", `guid` = "9ee6745f-f40d-46d8-8043-d959594628ce"; SET foreign_key_checks = 1'
        );

        $blogsCollection = $this->collectionManager->getCollection(self::BLOGS_BLOG);
        $usersCollection = $this->collectionManager->getCollection(self::USERS_USER);

        $user1 = $usersCollection->get('9ee6745f-f40d-46d8-8043-d959594628ce');
        $usersCollection->delete($user1);

        $blog1 = $blogsCollection->get($this->blog1Guid);
        $blogsCollection->delete($blog1);

        $e = null;
        try {
            $this->objectPersister->commit();
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $parentE = $e->getPrevious();
        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $parentE,
            'Ожидается родительское исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $this->assertEquals(
            'Cannot delete object with id "1" and type "blogs_blog.base". Database row is not modified.',
            $parentE->getMessage(),
            'Произошло неожидаемое исключение'
        );

    }

    public function testDeleteRollback()
    {
        $this->getDbCluster()
            ->modifyInternal('DELETE FROM `umi_mock_blogs` WHERE `id` = 1');

        $blogsCollection = $this->collectionManager->getCollection(self::BLOGS_BLOG);
        $usersCollection = $this->collectionManager->getCollection(self::USERS_USER);

        $user1 = $usersCollection->add();
        $user1->setValue('login', 'first_user');

        $blog3 = $blogsCollection->add('blog3');
        $blog3->setValue('title', 'third_blog');
        $blog3->setValue('owner', $user1);
        $blog3Guid = $blog3->getGUID();

        $blog1 = $blogsCollection->get($this->blog1Guid);
        $blogsCollection->delete($blog1);

        $blog2 = $blogsCollection->get($this->blog2Guid);
        $blog2->setValue('title', 'modified_second_blog');

        $e = null;
        try {
            $this->objectPersister->commit();
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $parentE = $e->getPrevious();
        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $parentE,
            'Ожидается родительское исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $this->assertEquals(
            'Cannot delete object with id "1" and type "blogs_blog.base". Database row is not modified.',
            $parentE->getMessage(),
            'Произошло неожидаемое исключение'
        );

        $this->assertTrue(
            $blog2->getIsUnloaded(),
            'Ожидается, что измененный объект был выгружен из менеджера объектов после роллбэка'
        );
        $this->assertTrue(
            $blog1->getIsUnloaded(),
            'Ожидается, что удаленный объект был выгружен из менеджера объектов после роллбэка'
        );
        $this->assertTrue(
            $blog3->getIsUnloaded(),
            'Ожидается, что добавленный объект был выгружен из менеджера объектов после роллбэка'
        );

        $e = null;
        try {
            $blogsCollection->get($blog3Guid);
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается исключение при попытке получить объект, который был добавлен, а потом был удален при откате'
        );
        $this->assertEquals(
            'second_blog',
            $blogsCollection->get($this->blog2Guid)
                ->getValue('title'),
            'Ожидается, что измененное свойство при откате вернулось к прежнему значению'
        );

        $this->assertCount(
            2,
            $this->collectionManager->getCollection(self::SYSTEM_HIERARCHY)
                ->select()
                ->fields(['id'])
                ->getResult()
                ->fetchAll(),
            'Ожидается, что в иерархической коллекции только 2 объектв'
        );
        $this->assertCount(
            0,
            $usersCollection->select()
                ->fields([IObject::FIELD_IDENTIFY])
                ->getResult()
                ->fetchAll(),
            'Ожидается, что в коллекции пользователей нет объектов'
        );

    }

    public function testUpdateRollback()
    {
        $this->getDbCluster()
            ->modifyInternal('DELETE FROM `umi_mock_blogs` WHERE `id` = 1');

        $blogsCollection = $this->collectionManager->getCollection(self::BLOGS_BLOG);

        $blog1 = $blogsCollection->get($this->blog1Guid);
        $blog1->setValue('title', 'new_blog_title');

        $e = null;
        try {
            $this->objectPersister->commit();
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $parentE = $e->getPrevious();
        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $parentE,
            'Ожидается родительское исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $this->assertEquals(
            'Cannot modify object with id "1" and type "blogs_blog.base". Database row is not modified.',
            $parentE->getMessage(),
            'Произошло неожидаемое исключение'
        );

    }

    public function testWrongVersionRollback()
    {
        $blogsCollection = $this->collectionManager->getCollection(self::BLOGS_BLOG);

        $blog1 = $blogsCollection->get($this->blog1Guid);
        $blog1->setVersion('3');
        $blog1->setValue('title', 'new_blog_title');

        $e = null;
        try {
            $this->objectPersister->commit();
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $parentE = $e->getPrevious();
        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $parentE,
            'Ожидается родительское исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $this->assertEquals(
            'Cannot modify object with id "1" and type "blogs_blog.base". Object is out of date.',
            $parentE->getMessage(),
            'Произошло неожидаемое исключение'
        );

    }

    public function testInsertRollback()
    {

        $this->getDbCluster()
            ->modifyInternal('ALTER TABLE `umi_mock_blogs` DROP FOREIGN KEY `FK_blog_owner`');
        $this->getDbCluster()
            ->modifyInternal(
            'ALTER TABLE `umi_mock_users` DROP PRIMARY KEY, MODIFY `id` bigint(20) unsigned DEFAULT NULL'
        );

        $usersCollection = $this->collectionManager->getCollection(self::USERS_USER);

        $user1 = $usersCollection->add();
        $user1->setValue('login', 'first_user');

        $e = null;
        try {
            $this->objectPersister->commit();
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $parentE = $e->getPrevious();
        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $parentE,
            'Ожидается родительское исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $this->assertEquals(
            'Cannot persist object. Cannot get last inserted id for object.',
            $parentE->getMessage(),
            'Произошло неожидаемое исключение'
        );

    }

    public function testHierarchyUpdateRollback()
    {

        $this->getDbCluster()
            ->modifyInternal('ALTER TABLE `umi_mock_blogs` DROP FOREIGN KEY `FK_blog_parent`');
        $this->getDbCluster()
            ->modifyInternal('ALTER TABLE `umi_mock_posts` DROP FOREIGN KEY `FK_post_parent`');
        $this->getDbCluster()
            ->modifyInternal('ALTER TABLE `umi_mock_hierarchy` DROP FOREIGN KEY `FK_hierarchy_parent`');
        $this->getDbCluster()
            ->modifyInternal(
            'ALTER TABLE `umi_mock_hierarchy` DROP PRIMARY KEY, DROP KEY `hierarchy_mpath`, DROP KEY `hierarchy_uri`, MODIFY `id` bigint(20) unsigned DEFAULT NULL'
        );
        $this->getDbCluster()
            ->modifyInternal('INSERT INTO `umi_mock_hierarchy` SET `id` = 3');

        $blogsCollection = $this->collectionManager->getCollection(self::BLOGS_BLOG);

        $blog3 = $blogsCollection->add('blog5');
        $blog3->getProperty(IObject::FIELD_IDENTIFY)
            ->setValue(3);
        $blog3->setValue('title', 'new_blog_title');

        $e = null;
        try {
            $this->objectPersister->commit();
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается исключение, когда не удается выполнить запросы на установку иерархических свойств'
        );
        $parentE = $e->getPrevious();
        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $parentE,
            'Ожидается родительское исключение, когда не удается выполнить запросы на изменения объектов'
        );
        $this->assertEquals(
            'Cannot set calculable properties for object with id "3" and type "blogs_blog.base". Database row is not modified.',
            $parentE->getMessage(),
            'Произошло неожидаемое исключение'
        );

    }

}
