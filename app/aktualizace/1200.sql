
UPDATE [:PREFIX:epodatelna] e1, [:PREFIX:epodatelna] e2 SET e1.[adresat] = e1.[odesilatel], e1.[odesilatel] = e2.[adresat] 
  WHERE e1.[id] = e2.[id] AND e1.[odchozi] = 1;
