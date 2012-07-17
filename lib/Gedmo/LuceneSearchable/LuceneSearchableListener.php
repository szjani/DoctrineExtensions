<?php
namespace Gedmo\LuceneSearchable;

use Doctrine\Common\EventArgs;
use Gedmo\Mapping\MappedEventSubscriber;
use Zend_Search_Lucene_Exception;
use Zend_Search_Lucene;
use Zend_Search_Lucene_Interface;
use Gedmo\LuceneSearchable\Mapping\Driver\Annotation;
use Zend_Search_Lucene_Document as LuceneDocument;
use Zend_Search_Lucene_Field as LuceneField;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * @author János Szurovecz <szjani@szjani.hu>
 * @package Gedmo.LuceneSearchable
 * @subpackage TreeListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LuceneSearchableListener extends MappedEventSubscriber
{
    const PK_NAME = 'pk';
    
    /**
     * @var array of Zend_Search_Lucene_Interface objects
     */
    private $zendLuceneIndexes = array();
    
    /**
     * @var boolean
     */
    protected $metaLoaded = false;
    
    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'postPersist',
            'loadClassMetadata',
            'preRemove',
            'preUpdate'
        );
    }
    
    /**
     * @return Zend_Search_Lucene_Interface
     */
    protected function getOrCreateZendLucene($objectClass, array $config)
    {
        if (!array_key_exists($objectClass, $this->zendLuceneIndexes)) {
            $path = $config[Annotation::LUCENE]['path'];
            try {
                $this->zendLuceneIndexes[$objectClass] = Zend_Search_Lucene::open($path);
            } catch (Zend_Search_Lucene_Exception $e) {
                $this->zendLuceneIndexes[$objectClass] = Zend_Search_Lucene::create($path);
            }
        }
        return $this->zendLuceneIndexes[$objectClass];
    }

    /**
     * Mapps additional metadata for the Entity
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        if ($this->metaLoaded) {
            return;
        }
        $this->metaLoaded = true;
        $ea = $this->getEventAdapter($eventArgs);
        $meta = $eventArgs->getClassMetadata();
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $meta);
        $om = $ea->getObjectManager();
        $repo = $om->getRepository($meta->name);
        $luceneIndex = $this->getOrCreateZendLucene($meta->name, $this->getConfiguration($om, $meta->name));
        if ($repo instanceof RepositoryInterface) {
            $repo->setLuceneIndex($luceneIndex);
            $repo->setIndexPkName(self::PK_NAME);
        }
    }
    
    /**
     * Retrieves the primary key of the given object.
     * 
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     * @param object $object
     * @return mixed
     */
    protected function getObjectId(ClassMetadata $meta, $object)
    {
        $idField = $meta->getIdentifier();
        return $meta->getReflectionProperty($idField[0])->getValue($object);
    }
    
    /**
     * Removes the related document of the object identified by $id
     * 
     * @param Zend_Search_Lucene_Interface $index
     * @param string $id
     */
    protected function removeIdFromIndex(Zend_Search_Lucene_Interface $index, $id)
    {
        foreach ($index->find('pk:' . $id) as $hit) {
            $index->delete($hit->id);
        }
        if (mt_rand(1, 10) == 1) {
            $index->optimize();
        } else {
            $index->commit();
        }
    }
    
    /**
     * Gets the current object from $args and removes it from the index.
     * 
     * @param \Doctrine\Common\EventArgs $args
     */
    protected function removeCurrentObjectFromItsIndex(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        
        $config = $this->getConfiguration($this->getEventAdapter($args)->getObjectManager(), $meta->name);
        $luceneIndex = $this->getOrCreateZendLucene($meta->name, $config);
        
        $this->removeIdFromIndex($luceneIndex, $this->getObjectId($meta, $object));
    }
    
    /**
     * Gets the current object from $args and adds it to the index.
     * 
     * @param \Doctrine\Common\EventArgs $args
     */
    protected function addCurrentObjectToItsIndex(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();

        $meta = $om->getClassMetadata(get_class($object));
        if ($config = $this->getConfiguration($om, $meta->name)) {
            if (isset($config[Annotation::LUCENE])) {
                $luceneIndex = $this->getOrCreateZendLucene($meta->name, $config);
                $document = new LuceneDocument();
                
                $idField = $meta->getIdentifier();
                $id = $meta->getReflectionProperty($idField[0])->getValue($object);
                $document->addField(LuceneField::keyword(self::PK_NAME, $id));
                
                if (isset($config[Annotation::BINARY])) {
                    foreach ($config[Annotation::BINARY] as $field) {
                        $value = $meta->getReflectionProperty($field['field'])->getValue($object);
                        $document->addField(LuceneField::binary($field['field'], $value, $field['encoding']));
                    }
                }
                if (isset($config[Annotation::KEYWORD])) {
                    foreach ($config[Annotation::KEYWORD] as $field) {
                        $value = $meta->getReflectionProperty($field['field'])->getValue($object);
                        $document->addField(LuceneField::keyword($field['field'], $value, $field['encoding']));
                    }
                }
                if (isset($config[Annotation::TEXT])) {
                    foreach ($config[Annotation::TEXT] as $field) {
                        $value = $meta->getReflectionProperty($field['field'])->getValue($object);
                        $document->addField(LuceneField::text($field['field'], $value, $field['encoding']));
                    }
                }
                if (isset($config[Annotation::UN_INDEXED])) {
                    foreach ($config[Annotation::UN_INDEXED] as $field) {
                        $value = $meta->getReflectionProperty($field['field'])->getValue($object);
                        $document->addField(LuceneField::unIndexed($field['field'], $value, $field['encoding']));
                    }
                }
                if (isset($config[Annotation::UN_STORED])) {
                    foreach ($config[Annotation::UN_STORED] as $field) {
                        $value = $meta->getReflectionProperty($field['field'])->getValue($object);
                        $document->addField(LuceneField::unStored($field['field'], $value, $field['encoding']));
                    }
                }
                
                $luceneIndex->addDocument($document);
                if (mt_rand(1, 10) == 1) {
                    $luceneIndex->optimize();
                } else {
                    $luceneIndex->commit();
                }
            }
        }
    }
    
    public function preUpdate(EventArgs $args)
    {
        $this->removeCurrentObjectFromItsIndex($args);
        $this->addCurrentObjectToItsIndex($args);
    }
    
    public function preRemove(EventArgs $args)
    {
        $this->removeCurrentObjectFromItsIndex($args);
    }

    /**
     * @param EventArgs $args
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
        $this->addCurrentObjectToItsIndex($args);
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

}
