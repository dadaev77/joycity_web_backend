UPDATE `client` SET `email` = CONCAT(MD5( `email` ),'@test.ru'), `old_email` = CONCAT(MD5( `old_email` ),'@test.ru'), `new_email` = CONCAT(MD5( `new_email` ),'@test.ru') WHERE 1


SELECT
CONCAT(
    SUBSTR(`email`,1,1),
    REPEAT('*',LOCATE('@',`email`)-3),
    SUBSTR(`email`,LOCATE('@',`email`)-1,3),
    REPEAT('*',LENGTH(`email`)-LOCATE('.',REVERSE(`email`))-LOCATE('@',`email`)-1),
    SUBSTR(`email`,LENGTH(`email`)-LOCATE('.',REVERSE(`email`)))
) from client where id=1;