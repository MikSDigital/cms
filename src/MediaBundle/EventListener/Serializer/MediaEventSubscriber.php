<?php

namespace Opifer\MediaBundle\EventListener\Serializer;

use Doctrine\Common\Cache\CacheProvider;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Opifer\MediaBundle\Model\MediaInterface;
use Opifer\MediaBundle\Provider\Pool;
use Opifer\MediaBundle\Provider\ProviderInterface;

class MediaEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var FilterConfiguration
     */
    protected $filterConfig;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param CacheManager        $cacheManager
     * @param FilterConfiguration $filterConfig
     * @param Pool                $pool
     * @param CacheProvider       $cache
     */
    public function __construct(CacheManager $cacheManager, FilterConfiguration $filterConfig, Pool $pool, CacheProvider $cache)
    {
        $this->cacheManager = $cacheManager;
        $this->filterConfig = $filterConfig;
        $this->pool = $pool;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize'],
        ];
    }

    /**
     * Listens to the post_serialize event and generates urls to the different image formats.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        // getSubscribedEvents doesn't seem to support parent classes
        if (!$event->getObject() instanceof MediaInterface) {
            return;
        }

        /** @var MediaInterface $media */
        $media = $event->getObject();

        $provider = $this->getProvider($media);

        if ($provider->getName() == 'image') {
            $images = $this->getImages($media, ['medialibrary']);

            $event->getVisitor()->addData('images', $images);
        }

        $event->getVisitor()->addData('original', $provider->getUrl($media));
    }

    /**
     * Gets the cached images if any. If the cache is not present, it generates images for all filters.
     *
     * @param MediaInterface $media
     * @param array $filters
     *
     * @return MediaInterface[]
     */
    public function getImages(MediaInterface $media, array $filters)
    {
        $key = $media->getImagesCacheKey();
        if (!$images = $this->cache->fetch($key)) {
            $provider = $this->getProvider($media);

            $reference = $provider->getThumb($media);

            $images = [];
            foreach ($filters as $filter) {
                if ($media->getContentType() == 'image/svg+xml') {
                    $images[$filter] = $provider->getUrl($media);
                } else {
                    $images[$filter] = $this->cacheManager->getBrowserPath($reference, $filter);
                }
            }

            $this->cache->save($key, $images, 0);
        }

        return $images;
    }

    /**
     * @param MediaInterface $media
     *
     * @return ProviderInterface
     */
    protected function getProvider(MediaInterface $media)
    {
        return $this->pool->getProvider($media->getProvider());
    }
}
