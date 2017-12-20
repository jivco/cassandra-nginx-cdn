tc qdisc add dev enp1s0 handle 1: root htb default 11
tc class add dev enp1s0 parent 1: classid 1:1 htb rate 50mbps
tc class add dev enp1s0 parent 1:1 classid 1:11 htb rate 50mbps
tc qdisc add dev enp1s0 parent 1:11 handle 10: netem delay 120ms 10ms loss 2%

tc qdisc del dev enp1s0 root
