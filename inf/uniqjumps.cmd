copy jumps.txt tmp.txt
sort tmp.txt | uniq > jumps.txt
del tmp.txt