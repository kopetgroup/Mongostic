#!/bin/sh
#require:
db='shelby'
col='users'
uname='rizoa'
pass='rizopoda'

#curl -X DELETE $uname':'$pass'@localhost:9200/'$db

i=1
while :
do

  if [ $i -gt 1 ]
  then
    skip=$(($(($i-1))*1000))
  else
    skip=0
  fi

  echo $db - $col

  last=`curl -X GET 'http://localhost:28017/'$db/$col'/?skip='$skip'&limit=1000'`
  total=`echo $last | jq -r '.total_rows'`
  rows=`echo $last | jq -r '.rows'`

  #echo $rows;
  for ((c=0; c<$total; c++ ))
  do
     data=`echo $last | jq '.rows['$c'] | with_entries(if .key == "_id" then .key = "id" else . end)'`
     idp=`echo $data | jq --raw-output .id`
     #echo $idp $data
     echo ""
     echo ""
     curl -XPUT $uname':'$pass'@localhost:9200/'$db'/'$col'/'$idp'?op_type=create&pretty' -H 'Content-Type: application/json' -d "$data"
  done

  #posisi
  echo $i $skip $total

  #itung sisa nek sisa <=1000 = kill
  if [ $total -gt 999 ]
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
