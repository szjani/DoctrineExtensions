<?php

namespace LuceneSearchable\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="LuceneSearchable\Fixture\ArticleRepository")
 * @Gedmo\Lucene(path="./index")
 */
class Article
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @Gedmo\LuceneText(encoding="utf-8")
     * @ORM\Column(name="title", type="string", length=8)
     */
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
    
}
