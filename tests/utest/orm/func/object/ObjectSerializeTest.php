<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace utest\orm\func\object;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\DebugStack;
use umi\orm\object\IObject;
use utest\orm\ORMDbTestCase;

/**
 * Тесты сериализации объекта
 */
class ObjectSerializeTest extends ORMDbTestCase
{
    /**
     * @var IObject $blog
     */
    protected $blog;
    protected $serialized = 'C:31:"umi\orm\object\HierarchicObject":512:{a:3:{i:0;a:17:{s:2:"id";s:1:"1";s:4:"guid";s:36:"9ee6745f-f40d-46d8-8043-d959594628ce";s:4:"type";s:15:"blogs_blog.base";s:7:"version";s:1:"2";s:6:"parent";N;s:5:"mpath";s:2:"#1";s:4:"slug";s:4:"blog";s:3:"uri";s:6:"//blog";s:10:"childCount";s:1:"0";s:5:"order";s:1:"1";s:5:"level";s:1:"0";s:11:"title#ru-RU";s:13:"russian title";s:11:"title#en-US";s:14:"american title";s:11:"title#en-GB";s:13:"british title";s:11:"title#ru-UA";N;s:11:"publishTime";N;s:5:"owner";s:1:"1";}i:1;s:10:"blogs_blog";i:2;s:4:"base";}}';
    protected $guid = '9ee6745f-f40d-46d8-8043-d959594628ce';

    /**
     * {@inheritdoc}
     */
    protected function getCollections()
    {
        return [
            self::USERS_GROUP,
            self::USERS_USER,
            self::SYSTEM_HIERARCHY,
            self::BLOGS_BLOG
        ];
    }

    protected function setUpFixtures()
    {
        $userCollection = $this->collectionManager->getCollection(self::USERS_USER);
        $blogCollection = $this->collectionManager->getCollection(self::BLOGS_BLOG);

        $this->blog = $blogCollection->add('blog');
        $user = $userCollection->add();
        $this->blog->setValue('title', 'russian title');
        $this->blog->setValue('title', 'american title', 'en-US');
        $this->blog->setValue('title', 'british title', 'en-GB');
        $this->blog->setValue('owner', $user);
        $this->blog->setGUID($this->guid);
    }

    public function testSerializeObject()
    {

        $e = null;
        try {
            serialize($this->blog);
        } catch (\Exception $e) {
        }
        $this->assertInstanceOf('\Exception', $e, 'Ожидается исключение при попытке сериализовать новый объект');

        $this->objectPersister->commit();
        $this->objectManager->unloadObjects();

        $blog = $this->collectionManager->getCollection(self::BLOGS_BLOG)
            ->get($this->guid);
        $this->setQueries([]);

        $serialized = serialize($blog);

        $this->assertEquals(
            [
                'SELECT `blogs_blog`.`id` AS `blogs_blog:id`, `blogs_blog`.`guid` AS `blogs_blog:guid`, `blogs_blog`.`type` AS `blogs_blog:type`, `blogs_blog`.`version` AS `blogs_blog:version`, `blogs_blog`.`pid` AS `blogs_blog:parent`, `blogs_blog`.`mpath` AS `blogs_blog:mpath`, `blogs_blog`.`slug` AS `blogs_blog:slug`, `blogs_blog`.`uri` AS `blogs_blog:uri`, `blogs_blog`.`title` AS `blogs_blog:title#ru-RU`, `blogs_blog`.`title_en` AS `blogs_blog:title#en-US`, `blogs_blog`.`title_gb` AS `blogs_blog:title#en-GB`, `blogs_blog`.`title_ua` AS `blogs_blog:title#ru-UA`
FROM `umi_mock_blogs` AS `blogs_blog`
WHERE ((`blogs_blog`.`id` = :value0))'
            ],
            $this->getQueries(),
            'Ожидается, что при сериализации объекта он полностью догрузит все свои свойства из базы'
        );

        $this->assertEquals($this->serialized, $serialized, 'Неверный результат сериализации объекта');

    }

    public function testUnserializeObject()
    {

        $this->objectPersister->commit();
        $this->setQueries([]);

        /**
         * @var IObject $blog
         */
        $blog = unserialize($this->serialized);
        $this->assertInstanceOf(
            'umi\orm\object\IObject',
            $blog,
            'Ожидается, что восстановление объекта вернет новый объект'
        );

        $e = null;
        try {
            $blog->setValue('title', 'new_title');
        } catch (\Exception $e) {
        }
        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается исключение при попытке изменить ансериализованный, но не инициированный объект'
        );

        $this->objectManager->wakeUpObject($blog);

        $this->assertInstanceOf(
            'umi\orm\object\IObject',
            $blog,
            'Ожидается, что восстановление объекта вернет новый объект'
        );

        $this->assertEquals(
            'russian title',
            $blog->getValue('title'),
            'Ожидается, что после инициализации объекта можно получить все его свойства'
        );
        $this->assertInstanceOf(
            'umi\orm\object\IObject',
            $blog->getValue('owner'),
            'Ожидается, что после инициализации объекта можно получить все его свойства'
        );

        foreach ($blog->getAllProperties() as $property) {
            $property->getValue();
        }

        $this->assertEmpty($this->getQueries(), 'Ожидается, что все значения свойства уже были загружены');
    }

    public function testUnserializeObjectAfterUnload()
    {

        $this->objectPersister->commit();
        $this->objectManager->unloadObjects();
        $this->setQueries([]);

        /**
         * @var IObject $blog
         */
        $blog = unserialize($this->serialized);
        $this->objectManager->wakeUpObject($blog);

        $this->assertInstanceOf(
            'umi\orm\object\IObject',
            $blog,
            'Ожидается, что восстановление объекта вернет новый объект'
        );

        foreach ($blog->getAllProperties() as $property) {
            if ($property->getName() == 'owner') {
                continue;
            }
            $property->getValue();
        }
        $this->assertEmpty($this->getQueries(), 'Ожидается, что все значения свойства уже были загружены');
        $this->assertInstanceOf(
            'umi\orm\object\IObject',
            $blog->getValue('owner'),
            'Ожидается, что после инициализации объекта можно получить все его свойства'
        );
        $this->assertCount(
            1,
            $this->getQueries(),
            'Ожидается, что после выгрузки всех объектов у ансериализованного объекта из базы '
            .'будут подгружаться только связи'
        );
    }
}
