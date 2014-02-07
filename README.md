Advance Cache [![Build Status](https://travis-ci.org/CEikermann/advcache.png?branch=master)](https://travis-ci.org/CEikermann/advcache)
===============
AdvCache is a extension of the Doctrine\Cache component. AdvCache implements also the Cache interface from Doctrine, but give you some additional flexible methods like fetchOrCall or cache tagging to have a better cache handling:

## Use case of cache tagging ##
For example, you wan to store a list of all friends of user #1. The user #1 has the following users as friends: user #3 and user #4. Properly you would select a cache key like "user_friends:1".
In case of user #4 deleted his account, you have to invalidate all cache of user friend lists where user #4 is in.
Now you could use the cache tagging system. Just tag the cache key "user_friends:1" with "user:4", because the "user:4" is in the list and you could invalidate all caches that assigned to the tag "user:4" in case of the account deletion.


Installation
============
Installation is super-easy via composer

```
composer require ceikermann/advcache
```

or add it to your composer.json file.


Usage
=====

## Changing default return value at fetch method
```php
$data = $advcache->fetch('somecacheid', array());
$data[] = 'some new data';
```

In case of the cache has not entry for ``somecacheid`` it will return an empty array instead of false.

Fetch or execute callback
---------------------------------
```php
$data = $advcache->fetchOrCall('somecacheid', function() {
    return $someNewData;
});
```

In this case if the cache has not entry for ``somecacheid`` it will execute the callback and save the result from the callback and return it.

Assign tags to cache
----------------------------------------------------
```php
$advcache->assignCacheIdToTag('somecacheid1', 'tag1');
$advcache->assignCacheIdToTag('somecacheid2', 'tag1');
```

In this case the tag1 is assign to two cache ids (`somecacheid1` and `somecacheid2`).

Delete by tag
----------------------------------------------------
```php
$advcache->deleteByCache('tag1');
```

In this case it will delete the cache of `somecacheid1` and `somecacheid2`, because both are assigned to `tag1`

Assign tags to cache at save method
----------------------------------------------------
```php
$advcache->save('somecacheid1', $someCachedData, 0, array('tag1', 'tag2));
```

It is possible to assign the cache directly with tags in save method
