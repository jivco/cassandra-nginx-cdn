# cassandra-nginx-cdn

Some config files and POC code to use Apache Cassandra as distributed storage for HLS chunks accross multiple datacenters and scripts for converting/transcoding UDP MPEG-TS to HLS and vice versa. The idea is take from Globo.com’s Live Video Platform for FIFA World Cup ’14.

- cassandra -> configs for Apache Cassandra
- dvr -> a Lua module to optimize Openresty config. This approach will be the most efficient as it will avoid re-creating the cluster variable on each request and will preserve the cached state of your load-balancing policy and prepared statements directly in the Lua land.
- openresty -> nginx config
- scripts -> converting/transcoding UDP MPEG-TS to HLS and vice versa and storing chunks in Apache Cassandra
- system -> some tips for instalation of DataStax's PHP driver and system config.
- test - some scripts to test if everything is working properly.
