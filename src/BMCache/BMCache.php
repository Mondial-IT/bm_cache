<?php
/*
 Copyright (c) Mondial-IT BV - Blue Marloc 2024
   Created on 2024-11-21 at 11:23:21
 */ /** @noinspection SqlResolve */

namespace Drupal\bm_cache\BMCache;

use Drupal;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Exception;
use stdClass;

/**
 * Custom cache service for BlueMarloc module.
 */
class BMCache {

  const PERMANENT = "bluemarloc_cache";

  const TEMPORARY = 'cache_bluemarloc';// managed by BMCache

  // temporary or permanent indicating bluemarloc_cache or cache_bluemarloc table.
  private mixed $namespace;

  // Constructor is private to enforce use of the factory method.
  // Factory method that creates an instance of BMCache.
  private function __construct($namespace = NULL) {
    if (!$namespace) {
      $namespace = 'bluemarloc_cache';
    }
    $this->namespace = $namespace;
  }

  /**
   * Check if bluemarloc_cache exists and create if not.
   *
   * @return void
   */
  private static function checkAndInstall(): void {
    $database = Drupal::database();
    $table_name = 'bluemarloc_cache';
    Drupal::logger('bm_main')->debug('Exists bluemarloc_cache?:[' . $database->schema()
        ->tableExists($table_name) . ']');
    // Check if the table exists.
    if (!$database->schema()->tableExists($table_name)) {
      Drupal::messenger()->addStatus('table bluemarloc_cache created');
      $sql = 'CREATE TABLE bluemarloc_cache LIKE cache_bluemarloc;';
      $database->query($sql)->execute();
      Drupal::logger('bm_main')->alert('Table bluemarloc_cache added to database');
      // signal table i
    }
    // signal the cache is available
    Drupal::State()->set('bm_main.BMCache', TRUE);
  }

  public static function cache($namespace = NULL): BMCache {

    if ($namespace === NULL || $namespace === 'temporary'|| $namespace=='bluemarloc') {
      $namespace = self::TEMPORARY;
    }
    else {
      $namespace = self::PERMANENT;
    }
    return new self($namespace);
  }

  /**
   * Get cached data.
   *
   * @param string $cid this is used in cache clears the top level
   *                    The cache ID.
   *
   * @return stdClass|null
   *   The cached data, or NULL if not found.
   */
  public function get(string $cid):false|object {
    if (!Drupal::State()->get('bm_main.BMCache')) {
      self::checkAndInstall();
    }

    if ($this->namespace === self::TEMPORARY) {
      // todo find out, why it has an invalid cache, ?
//      $cache=Drupal::cache('bluemarloc')->get($cid,true);
//      dsm($cache,__LINE__.'getting it from temp '.$cid);
      return Drupal::cache('bluemarloc')->get($cid,true);
    }

    // permanent table
    $database = Drupal::database();
    $row = false;
    try {
      $row = $database->query('SELECT * FROM bluemarloc_cache WHERE cid = :cid limit 1', [':cid' => $cid])
        ->fetchObject();

    } catch (DatabaseExceptionWrapper $e) {
      // create table when it is not there yet,
      if (str_contains($e->getMessage(), "bluemarloc_cache' doesn't exist")) {
        self::checkAndInstall();
        // not catching an exception, so when it failed it will now fail on an exception.
        $row = $database->query('SELECT data FROM bluemarloc_cache WHERE cid = :cid limit 1', [':cid' => $cid])
          ->fetchObject();
      }
    }

    if ($row) {
      $row->data = unserialize($row->data);
      $row->valid = true;
      return $row;
    }
    return FALSE;
  }
  /**
   * Set cached data.
   *
   * @param string $cid
   *   The cache ID.
   * @param mixed  $data
   *   The data to cache.
   * @param int    $expire
   *   (Optional) The expiration time in seconds.
   *
   * @throws Exception
   */
  public function set(string $cid, mixed $data, int $expire = 0): void {

    if ($this->namespace === self::TEMPORARY) {
      Drupal::cache('bluemarloc')->set($cid, $data, $expire);
      return;
    }

    // permanent tables

    // check if cache table exists else create it
    if (!Drupal::State()->get('bm_main.BMCache')) {
      self::checkAndInstall();
    }

    $database = Drupal::database();
    try {
      $checksum = 0;
      $expire=-1;
      $created = round(microtime(TRUE), 3);
      //'valid=true'
      $database->merge('bluemarloc_cache')
        ->key('cid', $cid)
        ->fields([
          'data' => serialize($data),
          'serialized' => TRUE,
          'checksum' => $checksum,
          'expire' => $expire,
          'created'=>$created
        ])
        ->execute();
    } catch (DatabaseExceptionWrapper $e) {

      // create table when it is not there yet,
      if (str_contains($e->getMessage(), "bluemarloc_cache' doesn't exist")) {
        self::checkAndInstall();
        // retry and fail on exception if it is not fixed.
        $database->merge('bluemarloc_cache')
          ->key('cid', $cid)
          ->fields([
            'data' => serialize($data),
            'serialized' => TRUE,
            'expire' => $expire,
            'created'=>$created
          ])
          ->execute();
      }
    }
  }

  public function delete($cid): static {

    if ($this->namespace === self::TEMPORARY) {
      Drupal::cache('bluemarloc')->delete($cid);
      return $this;
    }
    // use permanent table
    $database = Drupal::database();
    $database->delete('bluemarloc_cache')
      ->condition('cid', $cid)
      ->execute();

    Drupal::messenger()->addStatus("Deleting '$cid' from cache namespace '$this->namespace'");
    return $this;
  }


  public function list(): array {
    $table = self::TEMPORARY;
    if ($this->namespace !== self::TEMPORARY) {
      if (!Drupal::State()->get('bm_main.BMCache')) {
        self::checkAndInstall();
      }
      $table = self::PERMANENT;
    }

    $database = Drupal::database();
    $list = [];
    $entries = [];

    try {
      $entries = $database->query('SELECT cid, data FROM ' . $table)->fetchAll();
    } catch (DatabaseExceptionWrapper $e) {
      if ($table === self::PERMANENT) {
        // create table when it is not there yet,
        if (str_contains($e->getMessage(), "bluemarloc_cache' doesn't exist")) {
          self::checkAndInstall();
        }
      }
      else {
        throw new DatabaseExceptionWrapper($e);
      }
    }

    $cidCount = [];
    $cidSize = [];
    if ($entries) {
      foreach ($entries as $entry) {
        if (!isset($list[$entry->cid])) {
          $list[$entry->cid] = ['cid' => $entry->cid, 'count' => 0, 'size' => 0];
        }

        if (!isset($cidCount[$entry->cid])) {
          $cidCount[$entry->cid] = 0;
        }
        $cidCount[$entry->cid]++;
        $list[$entry->cid]['count'] = $cidCount[$entry->cid];

        if (!isset($cidSize[$entry->cid])) {
          $cidSize[$entry->cid] = 0;
        }

        $cidSize[$entry->cid] += strlen($entry->data);
        $list[$entry->cid]['size'] = $cidSize[$entry->cid];
      }
      return $list;
    }
    return [];
  }

  /**
   * Retrieve all entries with a matching CID
   * @param string $matchString matched as %matchString%
   *
   * @return array
   */
  public function match(string $matchString):array {
 //   dsm($matchString,__LINE__.' cache match');
    $table = $this->namespace;
    $matchString = Database::getConnection()->escapeLike($matchString);
    $query = Database::getConnection()->select($table, 't')
      ->fields('t', ['cid'])
      ->condition('cid', '%' . $matchString . '%', 'LIKE')
      ->range(0, 100); // Limit the results to 100.
    $matches = $query->execute()->fetchCol();
  //  dsm($matches,__LINE__.' matches');
    return $matches;
  }

}
