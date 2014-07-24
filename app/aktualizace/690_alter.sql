-- Patří logicky k následující aktulizaci 700, ale mění databázovou strukturu, což by zrušilo funkčnost transakce;
-- Proto musí být samostatně, pro případ, že by aktualizace 700 selhala;

ALTER TABLE `{tbls3}user_rule` 
    ADD COLUMN `order` INT NOT NULL DEFAULT '0',
    DROP COLUMN `note`;

