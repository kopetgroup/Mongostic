#!/bin/sh
#require:
db='shelby_keywords'
col='homedesign'
limit=1000
#curl -X DELETE localhost:9200/'$db'

i=1
while :
do

  if [ $i -gt 1 ]
  then
    skip=$(($(($i-1))*$limit))
  else
    skip=0
  fi

  echo $db - $col

  last=`curl -X GET 'http://localhost:28017/'$db/$col'/?skip='$skip'&limit='$limit`
  total=`echo $last | jq -r '.total_rows'`
  rows=`echo $last | jq --raw-output '.rows'`

  for ((c=0; c<$total; c++ ))
  do
     data=`echo $last | jq '.rows['$c'] | with_entries(if .key == "_id" then .key = "id" else . end)'`
     idp=`echo $data | jq --raw-output .id`
     echo '{ "index":{"_id":"'$idp'"}}'
     echo $data
  done > temp.elastik

  #posisi
  echo $i $skip $total

  curl -s -X POST 'rizoa:rizopoda@localhost:9200/'$db'/'$col'/_bulk?pretty&pretty' --data-binary @temp.elastik

  rm -rf './temp.elastik'
  sleep 10


  #itung sisa nek sisa <=1000 = kill
  if [ $total -gt $(($limit-1)) ]
  then
    echo 'lanjut'
    sleep 1
  else
    exit
    echo 'kill'
  fi

  sleep 1

  i=$(( $i + 1 ))
done
#
