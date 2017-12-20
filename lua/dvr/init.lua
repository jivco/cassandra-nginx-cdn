package.cpath = package.cpath .. ";/usr/lib64/lua/5.1/?.so"
local Cluster = require "resty.cassandra.cluster"

local cluster

local _M = {}

function _M.init_cluster(...)
  cluster = assert(Cluster.new(...))
  assert(cluster:refresh())
end

function _M.execute(...)
  return cluster:execute(...)
end

return _M
