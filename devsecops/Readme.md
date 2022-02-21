Migrate Influx database
-------------------------
1. Export influx database command:


    $ssh <user1>@oldserver
    $influx_inspect export -compress -out /tmp/devsecops_export -database devsecops_stats -datadir "/Dades/data/influxdb/data" -waldir="/Dades/data/influxdb/wal/"

2. Transfer compresed database to destination server


    $scp /tmp/devsecops_export  <user2>@newserver:/tmp/

3. Import influx database command:


    $ssh <user2>@newserver
    $influx -import -compressed -path="/tmp/devsecops_export
