<?php
/**
 * This file is part of the ceikermann/advcache package.
 *
 * (c) 2014 Christian Eikermann
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AdvCache;

use Doctrine\Common\Cache\Cache as DoctrineCache;

/**
 * AdvCache class
 *
 * @author Christian Eikermann <christian@chrisdev.de>
 */
class AdvCache implements DoctrineCache
{

    /**
     * @var DoctrineCache
     */
    protected $cache = null;

    /**
     * Constructor
     *
     * @param DoctrineCache $cache
     */
    public function __construct(DoctrineCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->cache->contains($id);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->cache->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return $this->cache->getStats();
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id      The id of the cache entry to fetch.
     * @param mixed  $default The default if no cache exists
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function fetch($id, $default = false)
    {
        return $this->cache->fetch($id) ?:$default;
    }

    /**
     * Tries to fetch an entry from the cache
     * If entity does not exists, emit the callback and the return will be put into the cache
     *
     * @param string   $id       Cache identifier
     * @param callable $callback Callback, if cache does not exists
     * @param int      $lifeTime Lifetime
     * @param array    $tags     Tags of the cache
     *
     * @return mixed The cached data or return from callback
     */
    public function fetchOrCall($id, $callback, $lifeTime = 0, array $tags = array())
    {
        if ($data = $this->fetch($id)) {
            return $data;
        }

        $data = call_user_func($callback);
        if ($data !== null && $data !== false) {
            $this->save($id, $data, $lifeTime, $tags);
        }

        return $data;
    }

    /**
     * Puts data into the cache.
     *
     * @param string $id       The cache id.
     * @param mixed  $data     The cache entry/data.
     * @param int    $lifeTime The cache lifetime.
     *                         If != 0, sets a specific lifetime for this cache entry (0 => infinite lifeTime).
     * @param array  $tags     The cache tags.
     *
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function save($id, $data, $lifeTime = 0, array $tags = array())
    {
        if ($this->cache->save($id, $data, $lifeTime)) {
            $this->assignCacheIdToTags($id, $tags);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes all caches that assigned with any tag
     *
     * @param array $tags List of cache tags
     */
    public function deleteByTags(array $tags)
    {
        foreach ($tags as $tag) {
            $this->deleteByTag($tag);
        }
    }

    /**
     * Deletes all caches that assigned with the tag
     *
     * @param string $tag The cache tag
     */
    public function deleteByTag($tag)
    {
        $id = $this->getTagCacheKey($tag);

        $cacheIds = $this->fetch($id, array());
        foreach ($cacheIds as $cacheId) {
            $this->delete($cacheId);
        }

        $this->delete($id);
    }

    /**
     * Assign cache id to all tags to
     *
     * @param string $cacheId The cache id
     * @param array  $tags    List of tags
     */
    public function assignCacheIdToTags($cacheId, array $tags)
    {
        foreach ($tags as $tag) {
            $this->assignCacheIdToTag($cacheId, $tag);
        }
    }

    /**
     * Assign cache id to tag
     *
     * @param string $cacheId The cache id
     * @param string $tag     The cache tag
     */
    public function assignCacheIdToTag($cacheId, $tag)
    {
        $id = $this->getTagCacheKey($tag);

        $cacheIds = $this->fetch($id, array());
        $cacheIds[] = $cacheId;

        // Remove double entries
        $cacheIds = array_unique($cacheIds);

        $this->save($id, $cacheIds);
    }

    /**
     * Generates the cache key for the tag
     *
     * @param string $tag The cache tag
     *
     * @return string Cache key of the tag
     */
    protected function getTagCacheKey($tag)
    {
        return sprintf('__advcache_tag[%s]', $tag);
    }

}