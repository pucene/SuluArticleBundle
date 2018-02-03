<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index\Factory;

use Sulu\Bundle\ArticleBundle\Document\TagViewObject;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;

/**
 * Create a collection with tag view objects.
 */
class TagCollectionFactory
{
    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * TagIndexerFactory constructor.
     *
     * @param TagManagerInterface $tagManager
     */
    public function __construct(TagManagerInterface $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    /**
     * Create tag collection.
     *
     * @param string[] $tagNames
     *
     * @return TagViewObject[]
     */
    public function create($tagNames)
    {
        $result = [];

        foreach ($tagNames as $tagName) {
            $tagEntity = $this->tagManager->findByName($tagName);
            if (!$tagEntity) {
                continue;
            }

            $tag = new TagViewObject();
            $tag->name = $tagName;
            $tag->id = $tagEntity->getId();

            $result[] = $tag;
        }

        return $result;
    }
}
