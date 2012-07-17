<?php
namespace Gedmo\LuceneSearchable;

use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Zend_Search_Lucene_Interface;
use Doctrine\ORM\QueryBuilder;

/**
 * @author János Szurovecz <szjani@szjani.hu>
 * @package Gedmo.LuceneSearchable
 * @subpackage RepositoryInterface
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class EntityRepository extends BaseEntityRepository implements RepositoryInterface
{
    /**
     * @var Zend_Search_Lucene_Interface
     */
    protected $luceneIndex;
    
    /**
     * @var string
     */
    protected $indexPkName;
    
    /**
     * @param Zend_Search_Lucene_Interface $luceneIndex
     */
    public function setLuceneIndex(Zend_Search_Lucene_Interface $luceneIndex)
    {
        $this->luceneIndex = $luceneIndex;
    }
    
    /**
     * @return Zend_Search_Lucene_Interface
     */
    public function getLuceneIndex()
    {
        return $this->luceneIndex;
    }
    
    /**
     * @return string
     */
    public function getIndexPkName()
    {
        return $this->indexPkName;
    }

    /**
     * @param string $pkName
     */
    public function setIndexPkName($pkName)
    {
        $this->indexPkName = $pkName;
    }
    
    /**
     * @param mixed $query
     * @return QueryBuilder
     */
    public function createQueryBuilderForLuceneQuery($query)
    {
        $ids = array();
        $pkName = $this->getIndexPkName();
        foreach ($this->luceneIndex->find($query) as $hit) {
            $ids[] = $hit->{$pkName};
        }
        $qb = $this->createQueryBuilder('e');
        $qb
            ->add('where', $qb->expr()->in('e.' . $this->_class->identifier[0], ':ids'))
            ->setParameter(':ids', $ids);
        return $qb;
    }
    
    /**
     * @param mixed $query
     * @param integer $hydrationMode
     * @return mixed
     */
    public function findByLuceneQuery($query, $hydrationMode = null)
    {
        return $this->createQueryBuilderForLuceneQuery($query)->getQuery()->execute($hydrationMode);
    }

}