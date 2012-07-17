<?php
namespace Gedmo\LuceneSearchable;

use Zend_Search_Lucene_Interface;

/**
 * @author János Szurovecz <szjani@szjani.hu>
 * @package Gedmo.LuceneSearchable
 * @subpackage RepositoryInterface
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface RepositoryInterface
{
    /**
     * @param Zend_Search_Lucene_Interface $luceneIndex
     */
    public function setLuceneIndex(Zend_Search_Lucene_Interface $luceneIndex);
    
    /**
     * @return Zend_Search_Lucene_Interface
     */
    public function getLuceneIndex();
    
    /**
     * @param string $pkName
     */
    public function setIndexPkName($pkName);
    
    /**
     * @return string
     */
    public function getIndexPkName();
    
    /**
     * @param mixed $query
     * @return QueryBuilder
     */
    public function createQueryBuilderForLuceneQuery($query);
    
    /**
     * @param mixed $query
     * @param integer $hydrationMode
     * @return mixed
     */
    public function findByLuceneQuery($query, $hydrationMode = null);
}