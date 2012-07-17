<?php
namespace Gedmo\LuceneSearchable\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriverInterface;

/**
 * @author János Szurovecz <szjani@szjani.hu>
 * @package Gedmo.LuceneSearchable.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements AnnotationDriverInterface
{
    const LUCENE = 'Gedmo\\Mapping\\Annotation\\Lucene';

    const BINARY = 'Gedmo\\Mapping\\Annotation\\LuceneBinary';

    const KEYWORD = 'Gedmo\\Mapping\\Annotation\\LuceneKeyword';

    const TEXT = 'Gedmo\\Mapping\\Annotation\\LuceneText';

    const UN_INDEXED = 'Gedmo\\Mapping\\Annotation\\LuceneUnIndexed';

    const UN_STORED = 'Gedmo\\Mapping\\Annotation\\LuceneUnStored';

    /**
     * Annotation reader instance
     *
     * @var object
     */
    private $reader;

    /**
     * original driver if it is available
     */
    protected $_originalDriver = null;

    /**
     * {@inheritDoc}
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $meta->getReflectionClass();
        
        if (!$class) {
            // based on recent doctrine 2.3.0-DEV maybe will be fixed in some way
            // this happens when running annotation driver in combination with
            // static reflection services. This is not the nicest fix
            $class = new \ReflectionClass($meta->name);
        }
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::LUCENE)) {
            $config[self::LUCENE]['path'] = $annot->path;
        }

        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            if ($propertyAnnot = $this->reader->getPropertyAnnotation($property, self::BINARY)) {
                $field = $property->getName();
                $config[self::BINARY][] = array('field' => $field, 'encoding' => $propertyAnnot->encoding);
            }
            if ($propertyAnnot = $this->reader->getPropertyAnnotation($property, self::KEYWORD)) {
                $field = $property->getName();
                $config[self::KEYWORD][] = array('field' => $field, 'encoding' => $propertyAnnot->encoding);
            }
            if ($propertyAnnot = $this->reader->getPropertyAnnotation($property, self::TEXT)) {
                $field = $property->getName();
                $config[self::TEXT][] = array('field' => $field, 'encoding' => $propertyAnnot->encoding);
            }
            if ($propertyAnnot = $this->reader->getPropertyAnnotation($property, self::UN_INDEXED)) {
                $field = $property->getName();
                $config[self::UN_INDEXED][] = array('field' => $field, 'encoding' => $propertyAnnot->encoding);
            }
            if ($propertyAnnot = $this->reader->getPropertyAnnotation($property, self::UN_STORED)) {
                $field = $property->getName();
                $config[self::UN_STORED][] = array('field' => $field, 'encoding' => $propertyAnnot->encoding);
            }
        }
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param $driver
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }
}