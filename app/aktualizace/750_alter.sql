-- ;

SELECT `id` INTO @VEDOUCI_RULE_ID FROM `{tbls3}user_rule` WHERE `privilege` = 'is_vedouci';
SELECT `id` INTO @MENIT_RULE_ID FROM `{tbls3}user_rule` WHERE `privilege` = 'menit_moje_oj';

INSERT IGNORE INTO `{tbls3}user_acl` (role_id, rule_id, `allowed`) 
  SELECT role_id, @MENIT_RULE_ID, 'Y' FROM `{tbls3}user_acl` WHERE rule_id = @VEDOUCI_RULE_ID AND `allowed` = 'Y';
