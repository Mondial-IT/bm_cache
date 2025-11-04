# Blue Marloc cache module

#### Temporary cache

* cleared on general cache clear

#### Permanent cache

* clear with specific clear commands


# Cache
Blue Marloc maintains its private caches.

### Temporary caches (drupal controlled)
These are identical and use the Drupal cache method.
`Cache clear all` clears this cache

Aliases:
* `Drupal::cache('bluemarloc')`
* `BMCache::cache('bluemarloc')`
* `BMCache::cache('temporary')`
* `BMCache::cache()`

### Permanent cache (not controlled)
This is the permanent cache in table bluemarloc_cache

`BMCache::cache('permanent')`

### Selective cash clear
The cache form enables clearing specific items from the caches

See cache menu Page to selectively clear cache elements.
