<?php
//namespace App\Plugins\Mongostic;

use \MongoDB\Client as Mongo;

class Mongoz {

  function mongo_collections($dbname){

    $client   = new Mongo;
    $e        = '';
    try {

      $collection      = $client->$dbname->command(['listCollections' => 1]);
      $vt           = [];
      foreach ($collection as $c) {
        $ns   = $c['name'];
        $cur  = $client->$dbname->$ns->count();
        $vt[] = [
          'key'       => $ns,
          'doc_count' => $cur
        ];
      }
      $st              = 'success';

    }catch(Exception $e){
      $st              = 'failed';
      $vt              = false;
    }

    return (object) [
      'database'      => $dbname,
      'collections'   => $vt,
      'status'        => $st
    ];


  }

  function mongo_insert($dbname,$collection,$data){

    $client   = new Mongo;
    $e        = '';
    try {

      $collection      = $client->$dbname->$collection;
      $insertOneResult = $collection->insertOne($data);
      $id              = $insertOneResult->getInsertedId();
      $id              = new ArrayIterator((array) $id);
      $id              = iterator_to_array($id, false)[0];
      $st              = 'success';

    }catch(Exception $e){
      $id              = 'kemungkinan duplicate';
      $st              = 'failed';
    }

    return (object) [
      '_id'     => $id,
      'status'  => $st
    ];

  }

  function mongo_update($dbname,$collection,$data){

    $client     = new Mongo;
    $colectme   = $client->$dbname->$collection;
    $result     = $colectme->updateOne(
      ['_id'    => $data['id']],
      ['$set'   => $data]
    );

    return [
      'matched' => $result->getMatchedCount(),
      'updated' => $result->getModifiedCount()
    ];

  }

  function mongo_delete_many($dbname,$collection,$find){
    /*
    $data = [
      'id' => 'keyword_kopet-idea-kopet-silit',
      'terpakai' => 'yes'
    ];
    */
    $client     = new Mongo;
    $colectme   = $client->$dbname->$collection;
    $result     = $colectme->deleteMany(
      $find,
      ['$set'   => $data]
    );

    return [
      'deleted' => $result->getDeletedCount()
    ];

  }

  function mongo_update_many($dbname,$collection,$find,$data){
    /*
    $data = [
      'id' => 'keyword_kopet-idea-kopet-silit',
      'terpakai' => 'yes'
    ];
    */
    $client     = new Mongo;
    $colectme   = $client->$dbname->$collection;
    $result     = $colectme->updateMany(
      $find,
      ['$set'   => $data]
    );

    return [
      'matched' => $result->getMatchedCount(),
      'updated' => $result->getModifiedCount()
    ];

  }

  function mongo_delete($dbname,$collection,$id){

    $client = new Mongo;
    $mongdl = $client->$dbname->$collection->deleteOne(['_id' => $id]);
    $res    = $mongdl->getDeletedCount();
    if($res==1){
      $st = 'success';
    }else{
      $st = 'failed';
    }
    return (object) [
      'status'  => $st,
      '_id'     => $id
    ];

  }

  function mongo_drop_collection($dbname,$collection){

    $client = new Mongo;
    $mongdc = $client->$dbname->$collection->drop();
    $aw     = [];
    foreach($mongdc as $w){
      $aw[] = $w;
    }
    return (object) $aw;

  }

  function mongo_drop($dbname=''){

    if($dbname!==''){

      $client   = new Mongo;
      $db       = $client->$dbname->drop();
      $iterator = new ArrayIterator((array) $db);
      $data     = (object)(iterator_to_array($iterator, false));
      return $data;

    }

  }

}
