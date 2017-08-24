<?php
//namespace App\Plugins\Mongostic;
use \Elasticsearch\ClientBuilder as Elastic;

class Elastiz {

  function hosts(){
    $hosts = [
      [
        'host'    => 'localhost',
        'port'    => '9200',
        'scheme'  => 'http',
        'user'    => 'rizoa',
        'pass'    => 'rizopoda'
      ]
    ];
    return $hosts;
  }
  function action(){

    $hosts   = $this->hosts();
    $elastic = new \Elasticsearch\ClientBuilder;
    return $elastic->create()->setHosts($hosts)->build();

  }

  function elastic_type_lists($index){

    $hosts  = $this->hosts()[0];
    $com    = 'curl -X GET \'http://'.$hosts['user'].':'.$hosts['pass'].'@'.$hosts['host'].':'.$hosts['port'].'/'.$index.'/_search\' -H \'Content-Type: application/json\' -d \'
    {
      "aggs": {
        "typesAgg": {
          "terms": {
            "field": "_type",
            "size": 200
          }
        }
      },
      "size": 0
    }
    \'';
    $res    = shell_exec($com);
    $res    = json_decode($res);
    $dat    = [
      'index'   => $index,
      'hits'    => $res->hits,
      'types'   => $res->aggregations->typesAgg->buckets
    ];
    return $dat;
  }

  function elastic_create_index($map){

    $json   = '
      {
        "index" : "'.$map.'",
        "body"  : {
          "settings" : {
            "number_of_shards"   : 7,
            "number_of_replicas" : 0
          },
          "mappings" : {
            "category" : {
              "properties" : {
                "category" : { "type" : "text" }
              }
            },
            "subcat" : {
              "properties" : {
                "subcat" : { "type" : "text" }
              }
            }
          }
        }
      }
    ';

    $response = $this->action()->indices()->create(json_decode($json));
    return $response;

  }

  function elastic_insert($index,$type,$data){

    //normalisasi elastik
    $id = $data['_id'];
    unset($data['_id']);

    $params = [
      'index' => $index,
      'type'  => $type,
      'id'    => $id,
      'body'  => $data
    ];
    $res = (object) $this->action()->index($params);
    return $res;

  }

  function elastic_update($index,$type,$data){
    $params = [
      'index' => $index,
      'type'  => $type,
      'id'    => $data['id'],
      'body' => [
        'doc' => $data
      ]
    ];
    $response = $this->action()->update($params);
    return $response;
  }

  function elastic_update_by_query($index,$type,$find,$data){

    $hosts = $this->hosts()[0];
    $aw    = [];
    foreach($data as $dk => $dv){
      foreach($find as $key => $val){
        $t     = 'curl -X POST "http://'.$hosts['user'].':'.$hosts['pass'].'@'.$hosts['host'].':'.$hosts['port'].'/'.$index.'/_update_by_query?wait_for_completion=true&conflicts=proceed&pretty" -H \'Content-Type: application/json\' -d\'
        {
          "script": {
            "inline": "ctx._source.'.$dk.' = \"'.$dv.'\""
          },
          "query": {
            "match_phrase": {
              "'.$key.'": "'.$val.'"
            }
          }
        }\'';
        //echo $t."<br/><br/><br/>";
      }
      sleep(1);
      $aw[$dk] = shell_exec($t);
    }
    return $aw;
  }

  function elastic_delete_by_query($index,$type,$find){

    $hosts = $this->hosts()[0];
    $aw    = [];

    foreach($find as $key => $val){
      $t     = 'curl -X POST "http://'.$hosts['user'].':'.$hosts['pass'].'@'.$hosts['host'].':'.$hosts['port'].'/'.$index.'/_delete_by_query?conflicts=proceed&pretty" -H \'Content-Type: application/json\' -d\'
      {
        "query": {
          "match_phrase": {
            "'.$key.'": "'.$val.'"
          }
        }
      }\'';
      $aw[$dk] = shell_exec($t);
    }
    return $aw;

  }

  function elastic_delete($index,$type,$id){
    try {

      $params = [
          'index' => $index,
          'type' => $type,
          'id' => $id
      ];
      $res = $this->action()->delete($params);
      $st  = 'success';

    }catch(Exception $res){

      $st  = 'failed';
      $res = false;

    }

    return (object) [
      'status' => $st,
      'result' => $res
    ];
  }

  function elastic_drop($index){

    $deleteParams = [
      'index' => $index
    ];
    $response = $this->action()->indices()->delete($deleteParams);
    return $response;

    //$res = shell_exec('curl -X DELETE http://localhost:9200/'.$index);
    //return json_decode($res);

  }

  //dev
  function elastic_drop_collection($index,$type){

    $com = 'curl -X POST \'http://localhost:9200/'.$index.'/_delete_by_query?conflicts=proceed\' -H \'Content-Type: application/json\' -d\'{"query": {"match": {"_type":"'.$type.'"}}}\'';
    $res = shell_exec($com);
    return json_decode($res);

  }

  //dev
  function reindex($index,$collection){

    //create map
    elastic_mapping($index);

    //get data from mongo rest
    $mg = 'http://localhost:28017/'.$index.'/'.$collection.'/?skip=0';
    $mg = shell_exec('curl -X GET '.$mg);
    $mg = json_decode($mg);

    echo $mg->total_rows;

  }

  //dev
  function elastic_map($index,$field,$term){

    $hosts = $this->hosts()[0];
    $aw    = [];
    $r     = 'curl -XGET \''.$hosts['user'].':'.$hosts['pass'].'@'.$hosts['host'].':'.$hosts['port'].'/'.$index.'/_search\' -H \'Content-Type: application/json\' -d\'
    {
      "query": {
        "match": {
          "'.$field.'": "'.$term.'"
        }
      },
      "sort": {
        "_score": "desc"
      },
      "aggs": {
        "'.str_replace(' ','_',$field).'": {
          "terms": {
            "field": "'.$field.'.raw"
          }
        }
      }
    }
    \'';
    $r = shell_exec($r);
    $r = json_decode($r);
    return $r;

  }

  //dev
  function elastic_mapping($index){
    try {
      $map = str_replace('{koleksi}',$index,file_get_contents('elastic_map.json'));
      return shell_exec('curl -X PUT \'localhost:9200/'.$index.'?pretty\' -H \'Content-Type: application/json\' -d\''.$map.'\'');
    }catch(Exception $map){
      return false;
    }
  }

}
