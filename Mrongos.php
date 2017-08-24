<?php
namespace App\Plugins\Mongostic;

require 'Elastiz.php';
require 'Mongoz.php';

use Elastiz as Elastik;
use Mongoz as Mongos;

class Mrongos {

  function status(){
    return 'ok';
  }

  function create_index($dbname){

    $ela  = new Elastik;
    $e    = $ela->elastic_create_index($dbname);
    return $e;

  }

  function elastic_type_lists($dbname){

    $ela  = new Elastik;
    $e    = $ela->elastic_type_lists($dbname,$collection,$query);
    return $e;

  }

  function get_collections($dbname){

    $ela  = new Elastik;
    $mon  = new Mongos;
    $e    = $ela->elastic_type_lists($dbname);
    $m    = $mon->mongo_collections($dbname);
    return (object) [
      'elastic' => $e,
      'mongo'   => $m
    ];

  }

  function search($dbname,$collection,$query){

    $ela  = new Elastik;
    $e    = $ela->elastic_search($dbname,$collection,$query);
    return $e;

  }

  function insert($dbname,$collection,$data){

    $ela = new Elastik;
    $mon = new Mongos;

    if($data['_id']){

      $m = $mon->mongo_insert($dbname,$collection,$data);
      $e = (object) ['status'=>'error'];
      if($m->status=='success'){
        $e = $ela->elastic_insert($dbname,$collection,$data);
      }
      return (object) [
        'elastic' => $e,
        'mongo'   => $m
      ];

    }
  }

  function update($dbname,$collection,$data){

    $ela = new Elastik;
    $mon = new Mongos;

    $m = $mon->mongo_update($dbname,$collection,$data);
    $e = $ela->elastic_update($dbname,$collection,$data);
    return (object) [
      'elastic' => $e,
      'mongo'   => $m
    ];
  }

  function update_many($dbname,$collection,$find,$data){

    $ela = new Elastik;
    $mon = new Mongos;

    $m = $mon->mongo_update_many($dbname,$collection,$find,$data);
    $e = $ela->elastic_update_by_query($dbname,$collection,$find,$data);
    return (object) [
      'elastic' => $e,
      'mongo'   => $m
    ];
  }

  function delete_many($dbname,$collection,$find){

    $ela = new Elastik;
    $mon = new Mongos;

    $m = $mon->mongo_delete_many($dbname,$collection,$find);
    $e = $ela->elastic_delete_by_query($dbname,$collection,$find);
    return (object) [
      'elastic' => $e,
      'mongo'   => $m
    ];
  }

  function delete($dbname,$collection,$id){

    $ela = new Elastik;
    $mon = new Mongos;

    $m = $mon->mongo_delete($dbname,$collection,$id);
    $e = $ela->elastic_delete($dbname,$collection,$id);
    return (object) [
      'elastic' => $e,
      'mongo'   => $m
    ];
  }

  function drop_collection($dbname,$collection){

    $ela = new Elastik;
    $mon = new Mongos;

    $m = $mon->mongo_drop_collection($dbname,$collection);
    $e = $ela->elastic_drop_collection($dbname,$collection);
    return (object) [
      'elastic' => $e,
      'mongo'   => $m
    ];
  }

  function drop($dbname){

    $ela = new Elastik;
    $mon = new Mongos;

    $m = $mon->mongo_drop($dbname,$collection,$id);
    $e = $ela->elastic_drop($dbname,$collection,$id);
    return (object) [
      'elastic' => $e,
      'mongo'   => $m
    ];
  }
}
