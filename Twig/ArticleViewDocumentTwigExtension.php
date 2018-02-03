<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Twig;

use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItem;
use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItemFactory;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Repository\ArticleViewDocumentRepository;
use Sulu\Bundle\ArticleBundle\Exception\ArticleInRequestNotFoundException;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Twig extension to retrieve article resource items from the article view document repository.
 */
class ArticleViewDocumentTwigExtension extends \Twig_Extension
{
    use StructureTagTrait;

    /**
     * @var ArticleViewDocumentRepository
     */
    protected $articleViewDocumentRepository;

    /**
     * @var ArticleResourceItemFactory
     */
    protected $articleResourceItemFactory;

    /**
     * @var ReferenceStoreInterface
     */
    protected $referenceStore;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $structureMetadataFactory;

    /**
     * @param ArticleViewDocumentRepository $articleViewDocumentRepository
     * @param ArticleResourceItemFactory $articleResourceItemFactory
     * @param ReferenceStoreInterface $referenceStore
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     * @param RequestStack $requestStack
     */
    public function __construct(
        ArticleViewDocumentRepository $articleViewDocumentRepository,
        ArticleResourceItemFactory $articleResourceItemFactory,
        ReferenceStoreInterface $referenceStore,
        StructureMetadataFactoryInterface $structureMetadataFactory,
        RequestStack $requestStack
    ) {
        $this->articleViewDocumentRepository = $articleViewDocumentRepository;
        $this->articleResourceItemFactory = $articleResourceItemFactory;
        $this->referenceStore = $referenceStore;
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->requestStack = $requestStack;
    }

    /**
     * Returns an array of possible function in this extension.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_article_load_recent', [$this, 'loadRecent']),
            new \Twig_SimpleFunction('sulu_article_load_similar', [$this, 'loadSimilar']),
        ];
    }

    /**
     * Loads recent articles with given parameters.
     *
     * @param int $limit
     * @param null|array $types
     * @param null|string $locale
     *
     * @return ArticleResourceItem[]
     */
    public function loadRecent(
        $limit = ArticleViewDocumentRepository::DEFAULT_LIMIT,
        array $types = null,
        $locale = null
    ) {
        $excludeUuid = null;

        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        $articleDocument = $request->get('object');
        if ($articleDocument instanceof ArticleDocument) {
            $excludeUuid = $articleDocument->getUuid();

            if (!$types) {
                $types = [$this->getArticleType($articleDocument)];
            }
        }

        if (!$locale) {
            $locale = $request->getLocale();
        }

        $articleViewDocuments = $this->articleViewDocumentRepository->findRecent($excludeUuid, $limit, $types, $locale);

        return $this->getResourceItems($articleViewDocuments);
    }

    /**
     * Loads similar articles with given parameters.
     *
     * @param int $limit
     * @param array|null $types
     * @param null $locale
     *
     * @throws ArticleInRequestNotFoundException
     *
     * @return ArticleResourceItem[]
     */
    public function loadSimilar(
        $limit = ArticleViewDocumentRepository::DEFAULT_LIMIT,
        array $types = null,
        $locale = null
    ) {
        $uuid = null;

        $request = $this->requestStack->getCurrentRequest();

        $articleDocument = $request->get('object');
        if ($articleDocument instanceof ArticleDocument) {
            $uuid = $articleDocument->getUuid();

            if (!$types) {
                $types = [$this->getArticleType($articleDocument)];
            }
        }

        if (!$uuid) {
            throw new ArticleInRequestNotFoundException();
        }

        if (!$locale) {
            $locale = $request->getLocale();
        }

        $articleViewDocuments = $this->articleViewDocumentRepository->findSimilar($uuid, $limit, $types, $locale);

        return $this->getResourceItems($articleViewDocuments);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_article.article_view_document';
    }

    /**
     * @param ArticleViewDocumentInterface[] $articleViewDocuments
     *
     * @return ArticleResourceItem[]
     */
    private function getResourceItems(array $articleViewDocuments)
    {
        $articleResourceItems = [];

        foreach ($articleViewDocuments as $articleViewDocument) {
            $this->referenceStore->add($articleViewDocument->getUuid());
            $articleResourceItems[] = $this->articleResourceItemFactory->createResourceItem($articleViewDocument);
        }

        return $articleResourceItems;
    }

    /**
     * @param ArticleDocument $articleDocument
     *
     * @return string
     */
    private function getArticleType(ArticleDocument $articleDocument)
    {
        $structureMetadata = $this->structureMetadataFactory->getStructureMetadata(
            'article',
            $articleDocument->getStructureType()
        );

        return $this->getType($structureMetadata);
    }
}
