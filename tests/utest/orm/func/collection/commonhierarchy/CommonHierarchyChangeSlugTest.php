<?php
use umi\dbal\builder\IQueryBuilder;
use umi\dbal\cluster\IConnection;
use umi\event\IEvent;
use umi\orm\collection\ICommonHierarchy;
use umi\orm\collection\ILinkedHierarchicCollection;
use umi\orm\metadata\IObjectType;
use utest\orm\ORMDbTestCase;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\DebugStack;

/**
 * Тест изменения последней части ЧПУ у объектов с общей иерархией
 */
class CommonHierarchyChangeSlugTest extends ORMDbTestCase
{

    protected $guid1;
    protected $guid2;
    protected $guid3;
    protected $guid4;

    /**
     * @var Connection $connection
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    protected function getCollections()
    {
        return array(
            self::USERS_GROUP,
            self::USERS_USER,
            self::SYSTEM_HIERARCHY,
            self::BLOGS_BLOG,
            self::BLOGS_POST,
        );
    }

    /**
     * @var ILinkedHierarchicCollection $blogsCollection
     */
    protected $blogsCollection;
    /**
     * @var ILinkedHierarchicCollection $postsCollection
     */
    protected $postsCollection;
    /**
     * @var ICommonHierarchy $hierarchy
     */
    protected $hierarchy;
    /**
     * @return array
     */
    protected function getQueries()
    {
        return array_values(
            array_map(
                function ($a) {
                    return $a['sql'];
                },
                $this->sqlLogger()->queries
            )
        );
    }

    /**
     * @param array $queries
     */
    public function setQueries($queries)
    {
        $this->sqlLogger()->queries = $queries;
    }

    /**
     * @return DebugStack
     */
    public function sqlLogger()
    {
        return $this->connection
            ->getConfiguration()
            ->getSQLLogger();
    }

    protected function setUpFixtures()
    {
        $this->connection = $this
            ->getDbCluster()
            ->getConnection();
        $this->connection
            ->getConfiguration()
            ->setSQLLogger(new DebugStack());

        $this->blogsCollection = $this->collectionManager->getCollection(self::BLOGS_BLOG);
        $this->postsCollection = $this->collectionManager->getCollection(self::BLOGS_POST);
        $this->hierarchy = $this->collectionManager->getCollection(self::SYSTEM_HIERARCHY);

        $blog1 = $this->blogsCollection->add('blog1');
        $blog1->setValue('title', 'test_blog');
        $this->guid4 = $blog1->getGUID();

        $post1 = $this->postsCollection->add('post1', IObjectType::BASE, $blog1);
        $post1->setValue('title', 'test_post');
        $this->guid1 = $post1->getGUID();

        $blog2 = $this->postsCollection->add('blog2', IObjectType::BASE, $post1);
        $blog2->setValue('title', 'test_blog2');
        $this->guid2 = $blog2->getGUID();

        $post3 = $this->postsCollection->add('post3', IObjectType::BASE, $post1);
        $post3->setValue('title', 'test_post3');
        $this->guid3 = $post3->getGUID();

        $this->objectPersister->commit();
        $this->objectManager->unloadObjects();
    }

    public function testUrl()
    {

        $blog1 = $this->blogsCollection->get($this->guid4);
        $post3 = $this->postsCollection->get($this->guid3);

        $this->assertEquals('//blog1/post1/post3', $post3->getURI());
        $this->assertEquals('//blog1', $blog1->getURI());

    }

    public function testChangeSlugPossibility()
    {

        $post2 = $this->postsCollection->get($this->guid2);

        $e = null;
        try {
            $this->hierarchy->changeSlug($post2, 'post3');
        } catch (\Exception $e) {
        }
        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается, что невозможно изменить uri объекта, если итоговый uri не уникальный'
        );

        $post2->setVersion(10);
        $e = null;
        try {
            $this->hierarchy->changeSlug($post2, 'new_slug');
        } catch (\Exception $e) {
        }
        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается, что невозможно изменить uri объекта, если его версия была ранее изменена'
        );

        $post2->setValue('title', '1');
        $e = null;
        try {
            $this->hierarchy->changeSlug($post2, 'new_slug');
        } catch (\Exception $e) {
        }
        $this->assertInstanceOf(
            'umi\orm\exception\RuntimeException',
            $e,
            'Ожидается, что невозможно изменить uri объекта, если есть модифицированные объекты'
        );

    }

    public function testChangeSlug()
    {

        $blog1 = $this->blogsCollection->get($this->guid4);
        $this->setQueries([]);
        $this->hierarchy->changeSlug($blog1, 'new_slug');

        $this->assertEquals(
            [
                //выбор затрагиваемых изменением slug коллекций
                'SELECT "type"
FROM "umi_mock_hierarchy"
WHERE "mpath" like #1.%
GROUP BY "type"',
                //проверка актуальности изменяемого объекта
                'SELECT "id"
FROM "umi_mock_hierarchy"
WHERE "id" = 1 AND "version" = 1',
                //проверка уникальности нового slug
                'SELECT "id"
FROM "umi_mock_hierarchy"
WHERE "uri" = //new_slug AND "id" != 1',
                //обновление всей slug у всей ветки изменяемого объекта
                'UPDATE "umi_mock_hierarchy"
SET "version" = "version" + 1, "uri" = REPLACE("uri", \'//blog1\', \'//new_slug\')
WHERE "uri" like //blog1/% OR "uri" = //blog1',
                'UPDATE "umi_mock_blogs"
SET "version" = "version" + 1, "uri" = REPLACE("uri", \'//blog1\', \'//new_slug\')
WHERE "uri" like //blog1/% OR "uri" = //blog1',
                'UPDATE "umi_mock_posts"
SET "version" = "version" + 1, "uri" = REPLACE("uri", \'//blog1\', \'//new_slug\')
WHERE "uri" like //blog1/% OR "uri" = //blog1'
            ],
            $this->getQueries(),
            'Неверные запросы на изменение slug в общей иерархической коллекции'
        );

        $post3 = $this->postsCollection->get($this->guid3);
        $blog2 = $this->postsCollection->get($this->guid2);

        $this->assertEquals('//new_slug/post1/post3', $post3->getURI());
        $this->assertEquals('//new_slug/post1/blog2', $blog2->getURI());
        $this->assertEquals('//new_slug', $blog1->getURI());
    }
}
