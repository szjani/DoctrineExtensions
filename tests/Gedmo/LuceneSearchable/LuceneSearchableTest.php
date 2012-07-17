<?php
namespace Gedmo\LuceneSearchable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Gedmo\LuceneSearchable\LuceneSearchableListener;
use Gedmo\LuceneSearchable\EntityRepository;
use LuceneSearchable\Fixture\Article;
use Zend_Search_Lucene_Interface;

class LuceneSearchableTest extends BaseTestCaseORM
{
    const ARTICLE = "LuceneSearchable\\Fixture\\Article";
    
    /**
     * @var Zend_Search_Lucene_Interface
     */
    private $index;
    
    /**
     * @var LuceneIndexedRepository
     */
    private $articleRepo;
    
    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
        );
    }
    
    public function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new LuceneSearchableListener());

        $this->getMockSqliteEntityManager($evm);
        
        $articleRepo = $this->em->getRepository('LuceneSearchable\Fixture\Article');
        $index = $articleRepo->getLuceneIndex();

        self::assertEquals(0, $index->count());
        $this->index = $index;
        $this->articleRepo = $articleRepo;
    }
    
    public function tearDown()
    {
        foreach ($this->index->find('pk:1') as $hit) {
            $this->index->delete($hit->id);
        }
        $this->index->optimize();
    }
    
    public function testRepository()
    {
        self::assertInstanceOf('Gedmo\LuceneSearchable\RepositoryInterface', $this->articleRepo);
        self::assertInstanceOf('Zend_Search_Lucene_Interface', $this->articleRepo->getLuceneIndex());
    }
    
    public function testPersist()
    {
        $article = new Article();
        $article->setTitle('test');
        $this->em->persist($article);
        $this->em->flush();
        $this->index->optimize();
        
        self::assertEquals(1, count($this->index->find('title:test')));
        self::assertInstanceOf('Zend_Search_Lucene_Interface', $this->index);
        self::assertEquals(1, $this->index->count());
    }
    
    public function testRemove()
    {
        $article = new Article();
        $article->setTitle('test');
        $this->em->persist($article);
        $this->em->flush();
        $this->index->optimize();
        self::assertEquals(1, $this->index->count());
        
        $this->em->remove($article);
        $this->em->flush();
        $this->index->optimize();
        
        self::assertEquals(0, $this->index->count());
    }
    
    public function testUpdate()
    {
        $article = new Article();
        $article->setTitle('test');
        $this->em->persist($article);
        $this->em->flush();
        $this->index->optimize();
        
        self::assertEquals(1, count($this->index->find('title:test')));
        
        $article->setTitle('modifiedTitle');
        $this->em->flush();
        $this->index->optimize();
        
        self::assertEquals(0, count($this->index->find('title:test')));
        self::assertEquals(1, count($this->index->find('title:modifiedTitle')));
    }
    
    public function testRepoFinder()
    {
        $article = new Article();
        $article->setTitle('test');
        $this->em->persist($article);
        $this->em->flush();
        $this->index->optimize();
        
        $res = $this->articleRepo->findByLuceneQuery('title:test');
        self::assertEquals(1, count($res));
    }
}