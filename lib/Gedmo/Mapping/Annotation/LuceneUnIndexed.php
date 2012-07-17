<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @author János Szurovecz <szjani@szjani.hu>
 * @package Gedmo.Mapping.Annotation
 * @subpackage LuceneSearchable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * 
 * @Annotation
 * @Target("PROPERTY")
 */
final class LuceneUnIndexed extends Annotation
{
    public $encoding = null;
}

