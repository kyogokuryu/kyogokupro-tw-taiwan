<?php

namespace Plugin\Collection;

use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Plugin\Collection\Repository\CollectionRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\TemplateEvent;

class Event implements EventSubscriberInterface
{
    /**
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * Event constructor.
     *
     * @param CollectionRepository $collectionRepository
     */
    public function __construct(CollectionRepository $collectionRepository) {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::FRONT_PRODUCT_INDEX_SEARCH => 'onFrontProductIndexSearch',
            'Product/list.twig' => 'collectionList',
        ];
    }

    public function collectionList(TemplateEvent  $event)
    {
        $event->addSnippet('@Collection/collection_list.twig');
    }

    public function onFrontProductIndexSearch(EventArgs $event)
    {
        $qb = $event->getArgument('qb');

        $collectionCode = $event->getRequest()->get('collection');
        if ($collectionCode !== null) {
            $cqb = $this->collectionRepository->createQueryBuilder('c');

            $today = (new \DateTime('today'))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            // display_from and display_to
            $cqb
                ->andWhere(
                    $cqb->expr()->orX(
                        $cqb->expr()->andX(
                            $cqb->expr()->isNotNull('c.display_from'),
                            $cqb->expr()->isNotNull('c.display_to'),
                            $cqb->expr()->andX(
                                $cqb->expr()->lte('c.display_from', "'{$today}'"),
                                $cqb->expr()->lte("'{$today}'", 'c.display_to')
                            )
                        ),
                        $cqb->expr()->andX(
                            $cqb->expr()->isNotNull('c.display_from'),
                            $cqb->expr()->isNull('c.display_to'),
                            $cqb->expr()->lte('c.display_from', "'{$today}'")
                        ),
                        $cqb->expr()->andX(
                            $cqb->expr()->isNull('c.display_from'),
                            $cqb->expr()->isNotNull('c.display_to'),
                            $cqb->expr()->lte("'{$today}'", 'c.display_to')
                        ),
                        $cqb->expr()->andX(
                            $cqb->expr()->isNull('c.display_from'),
                            $cqb->expr()->isNull('c.display_to')
                        )
                    )
                );

            $cqb->andWhere('c.collection_code = :collection_code')
                ->andWhere('c.visible = true')
                ->andWhere('c.deleted = false')
                ->setParameter('collection_code', $collectionCode);

            $Collection = $cqb->getQuery()->getOneOrNullResult();

            if ($Collection === null) {
                // wrong collection_code
                return;
            }

            $productIds = $Collection->getCollectionProducts()->map(function ($CollectionProduct) {
                return $CollectionProduct->getProduct()->getId();
            })->toArray();

            $qb
                ->andWhere($qb->expr()->in('p.id', $productIds));
        }
    }
}
